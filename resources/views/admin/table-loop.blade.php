<div class="table-responsive mb-0">
    <table class="table table-vcenter table-striped card-table mb-0">
        <thead>
            <tr class="bg-light text-muted small fw-bold">
                <th style="width: 50px;">NO</th>
                <th>NAMA CUSTOMER / TIM</th>
                <th>NOMOR WHATSAPP</th>
                <th>METODE DAFTAR</th>
                <th>STATUS VERIFIKASI</th>
                <th>JAM MASUK</th>
                <th class="text-end" style="width: 280px;">AKSI OPERASIONAL</th>
            </tr>
        </thead>
        <tbody>
    @if($items->isEmpty())
        <tr>
            <td colspan="7" class="text-center py-5 text-muted">
                <div class="mb-2" style="font-size: 24px;">🍃</div>
                <div class="fw-bold">Tidak ada data antrean pada kategori ini.</div>
            </td>
        </tr>
    @else
        @php $no = 1; @endphp
        @foreach ($items as $list)
            <tr class="fw-bold text-dark">
                <td><span class="badge bg-secondary-lt fw-bold">{{ $no++ }}</span></td>
                <td class="text-uppercase text-truncate" style="max-width: 180px;">{{ $list->customer_name }}</td>
                <td><span class="text-monospace text-secondary">{{ $list->phone_number ? '+62'.$list->phone_number : '-' }}</span></td>
                <td>
                    @if ($list->tipe === 'onsite')
                        <span class="badge bg-success-lt px-2 py-1">📍 ON-SITE KASIR</span>
                    @else
                        <span class="badge bg-orange-lt px-2 py-1">🌐 ONLINE WEB</span>
                    @endif
                </td>
                <td>
                    @if ($list->status === 'waiting')
                        <span class="badge bg-success px-2 py-1 text-white">READY</span>
                    @elseif ($list->status === 'not_verified')
                        <span class="badge bg-danger-lt px-2 py-1">⏳ BELUM LAPOR</span>
                    @elseif ($list->status === 'verified')
                        <span class="badge bg-blue-lt px-2 py-1">✔️ VERIFIED</span>
                    @elseif ($list->status === 'call')
                        <span class="badge bg-warning-lt blink-text px-2 py-1">📢 DIPANGGIL</span>
                    @elseif ($list->status === 'no_show')
                        <span class="badge bg-purple-lt px-2 py-1">❌ NO-SHOW / KABUR</span>
                    @elseif ($list->status === 'expired')
                        <span class="badge bg-dark-lt px-2 py-1">💨 EXPIRED</span>
                    @endif
                </td>
                <td class="text-muted small">{{ \Carbon\Carbon::parse($list->created_at)->format('H:i:s') }} WIB</td>
                <td class="text-end">
                    <div class="d-flex justify-content-end align-items-center gap-1">

                        <!-- JIKA ONLINE DAN BELUM VERIFIKASI (MUNCUL INPUT OTP) -->
                        @if($list->status === 'not_verified')
                            <form action="{{ route('admin.waitinglist.verify', $list->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                                @csrf
                                <input type="number" name="input_otp" class="form-control form-control-sm text-center fw-bold" placeholder="OTP WA" style="width: 85px;" required autocomplete="off">
                                <button type="submit" class="btn btn-sm btn-azure fw-bold">✔️ Check In</button>
                            </form>
                        @endif

                        <!-- JIKA ANTREAN AKTIF (BISA DIPANGGIL DAN DICORENG) -->
                        @if(in_array($list->status, ['waiting', 'verified', 'call']))
                            <!-- Tombol Panggil Pelanggan -->
                            <form action="{{ url('admin/waiting-list/panggil/'.$list->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning fw-bold">📢 Panggil</button>
                            </form>

                            <!-- Tombol Coreng / No-Show -->
                            <form action="{{ route('admin.waitinglist.skip', $list->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger fw-bold" onclick="return confirm('Apakah kamu yakin mengeluarkan tim ini?')">❌ Coreng</button>
                            </form>
                        @else
                            <span class="text-muted small italic">Selesai/Arsip</span>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    @endif
</tbody>
    </table>
</div>
