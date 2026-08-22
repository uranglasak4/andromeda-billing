<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaitingList;
use App\Models\Setting;
use App\Models\PoolTable; // <-- PENTING: Import model PoolTable di sini!
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WaitingListController extends Controller
{
    // 1. TAMPILKAN HALAMAN UTAMA KASIR + CEK OTOMATIS EXPIRED LAPIS 2
    public function index()
    {
        // Ambil menit regulasi dari setting master (contoh: 15 menit ke kasir)
        $limitMinutes = Setting::where('key', 'verification_time')->value('value') ?? 15;

        // [ONLINE WEB - LAPIS 2]: Otomatis Expired jika telat datang/lapor ke kasir
        $unverifiedQueues = WaitingList::where('tipe', 'online')
            ->where('status', 'not_verified')
            ->get();

        foreach ($unverifiedQueues as $queue) {
            // Gunakan verified_at (waktu lolos Lapis 1) sebagai acuan hitung 15 menit
            $startTime = $queue->verified_at ?? $queue->created_at;
            if (Carbon::parse($startTime)->addMinutes((int) $limitMinutes)->isPast()) {
                $queue->update(['status' => 'expired']);
            }
        }

        // -----------------------------------------------------------------------
        // HITUNG STATUS MEJA VS ANTREAN SESUAI LOGIKA
        // Pendaftaran DIBUKA jika: Total Waiting >= (Available + Timeout)
        // -----------------------------------------------------------------------
        $availableTables = PoolTable::whereIn('status', ['available', 'timeout'])->count();

        $totalWaiting = WaitingList::whereDate('created_at', Carbon::today())
            ->whereIn('status', ['waiting', 'not_verified', 'verified', 'call'])
            ->count();

        $canRegister = $totalWaiting >= $availableTables;

        // -----------------------------------------------------------------------
        // AMBIL SELURUH DATA ANTREAN HARI INI (Semua Status)
        // -----------------------------------------------------------------------
        $allWaitingLists = WaitingList::whereDate('created_at', Carbon::today())
            ->orderByRaw("
            CASE
                WHEN status = 'call' THEN 1
                WHEN tipe = 'onsite' OR status = 'verified' THEN 2
                WHEN status = 'not_verified' THEN 3
                ELSE 4
            END ASC
        ")
            ->orderBy('created_at', 'asc')
            ->get();

        // -----------------------------------------------------------------------
        // FILTER DATA UNTUK MASING-MASING TAB (Case-Insensitive)
        // -----------------------------------------------------------------------

        // 1. Tab Semua Aktif (Sesuai query bawaan Anda)
        $waitingLists = $allWaitingLists->filter(function ($item) {
            return in_array(strtolower($item->status), ['waiting', 'not_verified', 'verified', 'call']);
        });

        // 2. Tab On-Site
        $tabOnsite = $allWaitingLists->filter(function ($item) {
            return strtolower($item->tipe) === 'onsite' && in_array(strtolower($item->status), ['waiting', 'call']);
        });

        // 3. Tab Online Belum Verifikasi (Lapis 2)
        $tabOnlineUnverified = $allWaitingLists->filter(function ($item) {
            return strtolower($item->tipe) === 'online' && strtolower($item->status) === 'not_verified';
        });

        // 4. Tab Online Terverifikasi
        $tabOnlineVerified = $allWaitingLists->filter(function ($item) {
            return strtolower($item->tipe) === 'online' && in_array(strtolower($item->status), ['verified', 'call']);
        });

        // 5. Tab No-Show / Kabur
        $tabNoShow = $allWaitingLists->filter(function ($item) {
            return strtolower($item->status) === 'no_show';
        });

        // 6. Tab Expired / Gagal L2
        $tabExpired = $allWaitingLists->filter(function ($item) {
            return strtolower($item->status) === 'expired';
        });

        // 7. Tab Gagal L1 / Failed
        $tabFailed = $allWaitingLists->filter(function ($item) {
            return in_array(strtolower($item->status), ['failed', 'gagal']);
        });

        // 8. Tab Selesai / Check-In / Completed
        $tabDone = $allWaitingLists->filter(function ($item) {
            return in_array(strtolower($item->status), ['done', 'check_in', 'completed']);
        });

        return view('admin.waitinglist', compact(
            'waitingLists',
            'limitMinutes',
            'canRegister',
            'availableTables',
            'totalWaiting',
            'tabOnsite',
            'tabOnlineUnverified',
            'tabOnlineVerified',
            'tabNoShow',
            'tabExpired',
            'tabFailed',
            'tabDone'
        ));
    }

    // 2. PROSES SIMPAN ANTREAN (ONSITE & ONLINE)
    public function store(Request $request)
    {
        $fonnteToken = env('FONNTE_TOKEN');

        // Hitung ketersediaan meja vs antrean saat ini
        $availableTables = PoolTable::whereIn('status', ['available', 'timeout'])->count();
        $totalWaiting = WaitingList::whereDate('created_at', Carbon::today())
            ->whereIn('status', ['waiting', 'not_verified', 'verified', 'call'])
            ->count();

        // 🟢 PROTEKSI KASIR & ONLINE: Jika antrean lebih sedikit dari meja kosong/timeout, tolak pendaftaran!
        if ($totalWaiting < $availableTables) {
            return redirect()->back()->with('error', 'Pendaftaran WL ditutup! Kuota meja kosong/timeout masih mencukupi.');
        }

        // Hitung nomor urut berjalan berdasarkan antrean aktif hari ini
        $nextQueueNo = $totalWaiting + 1;

        // Format nomor WhatsApp ke standar 62
        $formattedPhone = null;
        if ($request->filled('nomor_wa')) {
            $formattedPhone = $request->nomor_wa;
            if (substr($formattedPhone, 0, 1) === '0') {
                $formattedPhone = '62' . substr($formattedPhone, 1);
            } elseif (substr($formattedPhone, 0, 2) !== '62') {
                $formattedPhone = '62' . $formattedPhone;
            }
        }

        $isClientAdmin = auth()->check() && auth()->user()->role === 'admin';

        if ($isClientAdmin) {
            // -------------------------------------------------------
            // [DAFTAR ON SITE] - Langsung Antrean Aktif
            // -------------------------------------------------------
            $request->validate([
                'nama_pelanggan' => 'required|string|max:18',
                'nomor_wa' => 'nullable|numeric',
            ]);

            WaitingList::create([
                'customer_name' => strtoupper($request->nama_pelanggan),
                'phone_number' => $request->nomor_wa ?? '-',
                'tipe' => 'onsite',
                'status' => 'waiting', // Onsite langsung masuk 'waiting'
            ]);

            if ($request->filled('nomor_wa')) {
                $pesanWA = "Selamat anda sudah terdaftar menjadi waiting list no #" . $nextQueueNo . " di Andromeda Billiard. Silahkan untuk pantau website kami lebih lanjut.";
                $this->kirimWA($formattedPhone, $pesanWA, $fonnteToken);
            }

            return redirect()->back()->with('success', 'Antrean On-Site berhasil ditambahkan langsung oleh Kasir!');

        } else {
            // -------------------------------------------------------
            // [DAFTAR ONLINE WEB] - Masuk Lapis 1 (Pending)
            // -------------------------------------------------------
            $request->validate([
                'nama_pelanggan' => 'required|string|max:25',
                'nomor_wa' => 'required|numeric',
            ]);

            $nomor = preg_replace('/\D/', '', $request->nomor_wa);

            if (strlen($nomor) < 9 || strlen($nomor) > 13) {
                return redirect()->back()->with('invalid_wa', 'Nomor WhatsApp tidak valid! Masukkan nomor yang benar (tanpa +62).');
            }

            if (substr($nomor, 0, 1) !== '8') {
                return redirect()->back()->with('invalid_wa', 'Nomor WhatsApp harus diawali angka 8. Contoh: 81234567890');
            }

            // Cek kuota online (hitung antrean aktif yang sudah lolos Lapis 1 dan yang terverifikasi)
            $maxOnlineQueue = Setting::where('key', 'max_online_queue')->value('value') ?? 15;
            $currentOnlineCount = WaitingList::where('tipe', 'online')
                ->whereIn('status', ['not_verified', 'verified'])
                ->count();

            if ($currentOnlineCount >= (int) $maxOnlineQueue) {
                return redirect()->back()->with('error', 'Maaf, kuota antrean online kami sedang penuh!');
            }

            $otpCode = rand(1000, 9999);

            // 🟢 Status awal diset 'pending' untuk Lapis 1
            $waitingList = WaitingList::create([
                'customer_name' => strtoupper($request->nama_pelanggan),
                'phone_number' => $formattedPhone,
                'tipe' => 'online',
                'status' => 'pending',
                'otp' => $otpCode,
            ]);

            $pesanWA = "Halo " . strtoupper($request->nama_pelanggan) . ",\n\nPendaftaran antrean ONLINE WEB BERHASIL!\n\nNomor Urut Anda: #" . $nextQueueNo . "\nKode OTP Verifikasi: " . $otpCode . "\n\nSilakan masukkan kode OTP di halaman website sebelum waktu 1 menit habis. Terima kasih.";

            $this->kirimWA($formattedPhone, $pesanWA, $fonnteToken);

            session(['waiting_list_id' => $waitingList->id]);

            return redirect()->route('customer.waiting-list.verify-page')->with([
                'success_trigger' => 'Kode OTP telah dikirimkan ke WhatsApp Anda!'
            ]);
        }
    }

    // 3. TAMPILKAN HALAMAN VERIFIKASI OTP LAPIS 1
    public function showVerifyPage()
    {
        if (!session()->has('waiting_list_id')) {
            return redirect()->route('customer.index');
        }
        return view('customer.verify');
    }

    // 4. CEK OTP LAPIS 1 (MANDIRI VIA WEB)
    public function checkOtpCustomer(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|numeric'
        ]);

        $waitingList = WaitingList::whereDate('created_at', Carbon::today())
            ->where('otp', $request->otp_code)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$waitingList) {
            return back()->withErrors(['otp_code' => 'Kode OTP salah, tidak valid, atau batas waktu verifikasi Anda sudah habis!']);
        }

        $waitingList->status = 'not_verified';
        $waitingList->verified_at = now(); // Timer 15 menit ke kasir dimulai dari sini
        $waitingList->save();

        session(['trigger_lapis_dua' => true]);
        session()->forget('waiting_list_id');

        return redirect()->route('customer.index');
    }

    // 5. TIMEOUT LAPIS 1 (AJAX BILA 1 MENIT OTP HABIS)
    public function handleTimeoutCustomer(Request $request)
    {
        $queue = WaitingList::find($request->id);

        if ($queue && $queue->status === 'pending') {
            $queue->update([
                'status' => 'failed',
                'otp' => null,
            ]);
            return response()->json(['status' => 'failed']);
        }

        return response()->json(['status' => 'no_action']);
    }

    // 6. VERIFIKASI KASIR LAPIS 2
    public function verifyPlayer(Request $request, $id)
    {
        $waitingList = WaitingList::find($id);

        if ($waitingList) {
            $waitingList->status = 'verified';
            $waitingList->verified_at = now();
            $waitingList->otp = null;
            $waitingList->save();

            return back()->with('success', 'Antrean berhasil diverifikasi penuh oleh Kasir!');
        }

        return back()->with('error', 'Data tidak ditemukan.');
    }

    // 7. TOMBOL PANGGIL KASIR
    public function panggilPlayer($id)
    {
        $queue = WaitingList::findOrFail($id);
        $queue->update(['status' => 'call']);

        if (!empty($queue->phone_number) && $queue->phone_number !== '-') {
            $formattedPhone = $queue->phone_number;
            if (substr($formattedPhone, 0, 1) === '0') {
                $formattedPhone = '62' . substr($formattedPhone, 1);
            }

            $pesanWA = "📢 PANGGILAN ANTREAN!\n\nHalo " . $queue->customer_name . ",\n\nSudah giliran Anda untuk bermain! Silahkan segera menuju ke meja kasir Andromeda Billiard untuk memilih meja. Terima kasih.";
            $this->kirimWA($formattedPhone, $pesanWA, env('FONNTE_TOKEN'));
        }

        return redirect()->back()->with('success', 'Antrean ' . $queue->customer_name . ' berhasil dipanggil!');
    }

    // 8. TOMBOL SKIP / NO SHOW
    public function skipPlayer($id)
    {
        $queue = WaitingList::findOrFail($id);
        $queue->update(['status' => 'no_show']);

        return redirect()->back()->with('warning', 'Antrean ' . $queue->customer_name . ' dicoreng dan dipindahkan ke Tab No-Show.');
    }

    // Helper Fonnte API
    private function kirimWA($target, $message, $token)
    {
        if (!$target || $target === '-')
            return;
        Http::withHeaders(['Authorization' => $token])->asForm()->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message
        ]);
    }
}
