<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaitingList;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WaitingListController extends Controller
{
    // 1. TAMPILKAN HALAMAN UTAMA KASIR + CEK OTOMATIS EXPIRED
    public function index()
    {
        // Ambil menit regulasi dari setting master (contoh: 2 menit)[cite: 19]
        $limitMinutes = Setting::where('key', 'verification_time')->value('value') ?? 20;

        // [ONLINE WEB - KONDISI 2]: Otomatis Expired jika telat verifikasi OTP[cite: 19]
        $expiredQueues = WaitingList::where('tipe', 'online')
            ->where('status', 'not_verified')
            ->get();

        foreach ($expiredQueues as $queue) {
            if (Carbon::parse($queue->created_at)->addMinutes((int) $limitMinutes)->isPast()) {
                $queue->update(['status' => 'expired']);
            }
        }

        // Ambil semua data antrean hari ini untuk di-filter di Tab Blade[cite: 19]
        $waitingLists = WaitingList::whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.waitinglist', compact('waitingLists', 'limitMinutes'));
    }

    // 2. PROSES SIMPAN ANTREAN (ONSITE & ONLINE)
    public function store(Request $request)
    {
        $fonnteToken = env('FONNTE_TOKEN'); //[cite: 19]

        // Hitung nomor urut berjalan berdasarkan antrean aktif hari ini[cite: 19]
        $nextQueueNo = WaitingList::whereDate('created_at', Carbon::today())
            ->whereIn('status', ['waiting', 'not_verified', 'verified', 'call'])
            ->count() + 1;

        // Format nomor WhatsApp ke standar 62[cite: 19]
        $formattedPhone = null;
        if ($request->filled('nomor_wa')) {
            $formattedPhone = $request->nomor_wa;
            if (substr($formattedPhone, 0, 1) === '0') {
                $formattedPhone = '62' . substr($formattedPhone, 1);
            } elseif (substr($formattedPhone, 0, 2) !== '62') {
                $formattedPhone = '62' . $formattedPhone;
            }
        }

        $isClientAdmin = auth()->check() && auth()->user()->role === 'admin'; //[cite: 19]

        if ($isClientAdmin) {
            // -------------------------------------------------------
            // [DAFTAR ON SITE - KONDISI 1]
            // -------------------------------------------------------
            $request->validate([
                'nama_pelanggan' => 'required|string|max:25',
                'nomor_wa' => 'nullable|numeric',
            ]); //[cite: 19]

            WaitingList::create([
                'customer_name' => strtoupper($request->nama_pelanggan),
                'phone_number' => $request->nomor_wa ?? '-',
                'tipe' => 'onsite',
                'status' => 'waiting', // Langsung masuk antrean aktif
            ]); //[cite: 19]

            // Kirim WA jika kasir menginput nomor WhatsApp
            if ($request->filled('nomor_wa')) {
                $pesanWA = "Selamat anda sudah terdaftar menjadi waiting list no #" . $nextQueueNo . " di Andromeda Billiard. Silahkan untuk pantau website kami lebih lanjut.";
                $this->kirimWA($formattedPhone, $pesanWA, $fonnteToken);
            }

            return redirect()->back()->with('success', 'Antrean On-Site berhasil ditambahkan langsung oleh Kasir!'); //[cite: 19]

        } else {
            // -------------------------------------------------------
            // [DAFTAR ONLINE WEB - KONDISI 1]
            // -------------------------------------------------------
            $request->validate([
                'nama_pelanggan' => 'required|string|max:25',
                'nomor_wa' => 'required|numeric',
            ]); //[cite: 19]

            $nomor = preg_replace('/\D/', '', $request->nomor_wa); // hapus non-angka

            // Panjang nomor WA Indonesia: 9-13 digit (tanpa kode negara)
            if (strlen($nomor) < 9 || strlen($nomor) > 13) {
                return redirect()->back()->with('invalid_wa', 'Nomor WhatsApp tidak valid! Masukkan nomor yang benar (tanpa +62).');
            }

            // Pastikan diawali 8 (standar nomor HP Indonesia)
            if (substr($nomor, 0, 1) !== '8') {
                return redirect()->back()->with('invalid_wa', 'Nomor WhatsApp harus diawali angka 8. Contoh: 81234567890');
            }
            // Cek kuota online[cite: 19]
            $maxOnlineQueue = Setting::where('key', 'max_online_queue')->value('value') ?? 15; //[cite: 19]
            $currentOnlineCount = WaitingList::where('tipe', 'online')
                ->where('status', 'not_verified')
                ->count();

            if ($currentOnlineCount >= (int) $maxOnlineQueue) {
                return redirect()->back()->with('error', 'Maaf, kuota antrean online kami sedang penuh!'); //[cite: 19]
            }

            $limitMinutes = Setting::where('key', 'verification_time')->value('value') ?? 2;
            $otpCode = rand(1000, 9999); //[cite: 19]

            WaitingList::create([
                'customer_name' => strtoupper($request->nama_pelanggan),
                'phone_number' => $request->nomor_wa,
                'tipe' => 'online',
                'status' => 'not_verified', // Belum verifikasi
                'otp' => $otpCode, //[cite: 19]
            ]);

            $pesanWA = "Halo " . strtoupper($request->nama_pelanggan) . ",\n\nPendaftaran antrean ONLINE WEB BERHASIL! 🎉\n\n📌 Nomor Urut Anda: #" . $nextQueueNo . "\n🔐 Kode OTP Verifikasi: " . $otpCode . "\n\n⚠️ Silakan ke kasir untuk melakukan verifikasi dalam waktu maksimal " . $limitMinutes . " menit dari sekarang sebelum antrean Anda kedaluwarsa (Expired). Terima kasih."; //[cite: 19]

            $this->kirimWA($formattedPhone, $pesanWA, $fonnteToken); //[cite: 19]

            return redirect()->back()->with('success_online', 'Pendaftaran Berhasil! Kode OTP telah dikirimkan ke WhatsApp Anda.'); //[cite: 19]
        }
    }

    // 3. [ONLINE WEB - KONDISI 3]: VERIFIKASI KASIR VIA OTP
    public function verifyPlayer(Request $request, $id)
    {
        $queue = WaitingList::findOrFail($id); //[cite: 19]

        if ($request->input('input_otp') != $queue->otp) {
            return redirect()->back()->with('warning', 'Kode OTP yang dimasukkan salah!'); //[cite: 19]
        }

        $queue->update(['status' => 'verified']); // Status berubah jadi verified

        return redirect()->back()->with('success', 'Status pelanggan online berhasil diverifikasi (Verified)!'); //[cite: 19]
    }

    // 4. [ON SITE KONDISI 3] & [ONLINE WEB KONDISI 4]: TOMBOL PANGGIL KASIR
    public function panggilPlayer($id)
    {
        $queue = WaitingList::findOrFail($id);
        $queue->update(['status' => 'call']); // Set ke status call

        if (!empty($queue->phone_number) && $queue->phone_number !== '-') {
            $formattedPhone = $queue->phone_number;
            if (substr($formattedPhone, 0, 1) === '0') {
                $formattedPhone = '62' . substr($formattedPhone, 1);
            }

            $pesanWA = "📢 PANGGILAN ANTREAN!\n\nHalo " . $queue->customer_name . ",\n\nSudah giliran Anda untuk bermain! 🎱 Silahkan segera menuju ke meja kasir Andromeda Billiard untuk memilih meja yang tersedia. Terima kasih.";
            $this->kirimWA($formattedPhone, $pesanWA, env('FONNTE_TOKEN'));
        }

        return redirect()->back()->with('success', 'Panggilan berhasil dikirim via WhatsApp!');
    }

    // 5. [ON SITE KONDISI 2] & [ONLINE WEB KONDISI 5]: TOMBOL COREG / SAKIP
    public function skipPlayer($id)
    {
        $queue = WaitingList::findOrFail($id);

        // Langsung diubah ke status tunggal no_show
        $queue->update(['status' => 'no_show']);

        return redirect()->back()->with('warning', 'Antrean ' . $queue->customer_name . ' dikeluarkan dan ditandai sebagai No-Show.'); //[cite: 19]
    }

    // Fungsi Pembantu Pengiriman API Fonnte[cite: 19]
    private function kirimWA($target, $message, $token)
    {
        if (!$target || $target === '-')
            return;
        Http::withHeaders(['Authorization' => $token])->asForm()->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message
        ]); //[cite: 19]
    }
}
