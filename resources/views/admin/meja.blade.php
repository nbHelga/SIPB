@extends('layouts.admin')

@section('title', 'Denah & Kelola Meja - SIPB')

@section('content')
<div x-data="alokasiMejaApp()" class="w-full pb-10">
    <x-alert type="client-notification" />

    @if(session('success'))
        <x-alert type="success" :message="session('success')" :duration="5000" />
    @endif

    @if(session('error'))
        <x-alert type="error" :message="session('error')" :duration="5000" />
    @endif

    @if($errors->any())
        <x-alert type="error" :message="$errors->first()" :duration="5000" />
    @endif

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-4 border-stone-950 pb-4 mb-6">
        <div>
            <h1 class="text-2xl font-black">🗺️ Alokasi Meja Reservasi</h1>
            <p class="text-xs text-stone-500 mt-1">* Klik Mode Alokasi untuk plot meja pengunjung.</p>
        </div>
        <div class="mt-2 flex items-center gap-3 text-sm font-bold">
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500"></span> Kosong</div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500"></span> Dipakai</div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500"></span> Dipesan</div>
        </div>
    </div>

    <!-- NAVBAR TAB & CONTROLS -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-stone-200 p-2 rounded-xl border-2 border-stone-900 shadow-[3px_3px_0_0_rgba(0,0,0,1)] mb-6">
        
        <!-- Tab Lantai -->
        <div class="flex items-center gap-1.5 flex-wrap">
            @foreach($lantaiList as $l)
                <button type="button" 
                        @click="currentTab = '{{ $l }}'"
                        :class="currentTab == '{{ $l }}' ? 'bg-[#4A2311] text-white shadow-inner' : 'text-stone-700 hover:bg-stone-300'"
                        class="px-4 py-2 text-sm font-black rounded-lg transition-all duration-150">
                    Lantai {{ $l }}
                </button>
            @endforeach
        </div>

        <!-- Plotting Tools -->
        <div class="flex items-center gap-2">
            <button type="button" @click="togglePlotting()" :class="isPlotting ? 'bg-amber-500 text-stone-900 border-stone-900' : 'bg-[#4A2311] text-white border-[#4A2311]'" class="px-4 py-2 text-sm font-black rounded-lg border-2 shadow-[2px_2px_0_0_rgba(0,0,0,1)] transition">
                <span x-text="isPlotting ? 'Batal Alokasi' : '📍 Mode Alokasi'"></span>
            </button>

            <div x-show="isPlotting" style="display: none;" class="flex items-center gap-2">
                <select x-model="selectedResId" @change="onSelectRes()" class="rounded-lg border-2 border-stone-900 px-3 py-2 text-sm font-bold bg-white text-stone-900 focus:outline-none focus:ring-2 focus:ring-[#4A2311]">
                    <option value="">-- Pilih Reservasi --</option>
                    <!-- HAPUS reservationsForSelectedDate(), GUNAKAN reservations AGAR DOM TIDAK CRASH -->
                    <template x-for="r in reservations" :key="r.id">
                        <option :value="r.id" 
                                x-text="`${r.tgl_formatted} (${r.jam_mulai} - ${r.jam_selesai}) - ${r.customer_nama}`">
                        </option>
                    </template>
                </select>
                <button type="button" @click="savePlotting()" class="px-4 py-2 bg-emerald-600 text-white text-sm font-black rounded-lg border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)] hover:bg-emerald-700">
                    💾 Simpan
                </button>
            </div>
        </div>
    </div>
    <div class="mb-5 rounded-xl border-2 border-amber-500 bg-amber-50 p-3 text-sm font-bold text-amber-950">
        Alokasikan seluruh meja reservasi sekaligus sebelum menyimpan. Alokasi bertahap dapat membuat kapasitas yang terlihat untuk reservasi lain tidak akurat.
    </div>

    <!-- RENDER DENAH MEJA -->
    @foreach($denahMeja as $lantai => $daftarMeja)
        <div x-show="currentTab == '{{ $lantai }}'" style="display: none;" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            
            @foreach($daftarMeja as $meja)
                 <div x-show="!isPlotting || !selectedResId || {{ $meja->is_active ? 'true' : 'false' }}"
                     :class="getMejaClass('{{ $meja->id }}')"
                     @click="toggleMeja('{{ $meja->id }}')" 
                     class="border-4 border-stone-900 rounded-xl p-4 flex flex-col justify-between relative min-h-[150px] transition-all select-none">
                     
                    <div class="flex items-center gap-3">
                        <span class="w-4 h-4 rounded-full mr-2 flex-shrink-0 border-2 border-stone-900/20" :class="getLampClass('{{ $meja->id }}')"></span>
                        <div class="flex flex-col">
                            <span class="text-xl font-black text-stone-800">#{{ $meja->nomor_meja }}</span>
                            <span class="text-[10px] uppercase font-bold text-stone-500">Lantai {{ $meja->lantai }}</span>
                            <span class="text-[10px] font-black uppercase {{ $meja->is_active ? 'text-emerald-700' : 'text-stone-500' }}">{{ $meja->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2" x-show="!isPlotting" @click.stop>
                        @if($canManageTables)
                        <form method="POST" action="{{ route('admin.meja.update', $meja->id) }}" class="flex items-center gap-1.5">
                            @csrf
                            @method('PUT')
                            <input type="text" name="nomor_meja" value="{{ $meja->nomor_meja }}" required maxlength="10" class="min-w-0 w-20 rounded border border-stone-400 px-1.5 py-1 text-xs font-bold" aria-label="Nomor meja">
                            <select name="lantai" class="min-w-0 flex-1 rounded border border-stone-400 px-1.5 py-1 text-xs font-bold" aria-label="Pindahkan meja {{ $meja->nomor_meja }} ke lantai">
                                <option value="1" @selected((string) $meja->lantai === '1')>Lantai 1</option>
                                <option value="2" @selected((string) $meja->lantai === '2')>Lantai 2</option>
                            </select>
                            <button type="submit" class="rounded border border-stone-700 bg-white px-2 py-1 text-[10px] font-bold hover:bg-stone-100">Simpan</button>
                        </form>
                        <form method="POST" action="{{ route('admin.meja.delete', $meja->id) }}" onsubmit="return confirm('Hapus meja ini? Riwayat lama akan tetap tersimpan tanpa nomor meja.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded border border-red-700 bg-red-50 px-2 py-1 text-[10px] font-bold text-red-700 transition hover:bg-red-100">Hapus meja</button>
                        </form>
                        <form method="POST" action="{{ route('admin.meja.active', $meja->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $meja->is_active ? '0' : '1' }}">
                            <button type="submit" class="w-full rounded border border-stone-700 px-2 py-1 text-[10px] font-bold transition {{ $meja->is_active ? 'bg-stone-200 text-stone-700 hover:bg-stone-300' : 'bg-emerald-700 text-white hover:bg-emerald-800' }}">
                                {{ $meja->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        @endif
                    </div>

                    <!-- Highlight Badge Tabrakan Waktu -->
                    <div x-show="isPlotting && conflictTables.includes('{{ $meja->id }}')" style="display:none;" class="absolute -top-3 -right-3 bg-red-600 border-2 border-white text-white text-[10px] font-black px-2 py-1 rounded-full shadow-lg z-10 animate-pulse">
                        Tabrakan Waktu
                    </div>
                </div>
            @endforeach

            @if($canManageTables)
                <button type="button" @click="openAddTable('{{ $lantai }}')" x-show="!isPlotting"
                    class="flex min-h-[150px] items-center justify-center rounded-xl border-4 border-dashed border-stone-400 bg-stone-100 text-5xl font-black text-stone-500 transition hover:border-stone-700 hover:bg-stone-200 hover:text-stone-900"
                    aria-label="Tambah meja di lantai {{ $lantai }}" title="Tambah meja di lantai {{ $lantai }}">+</button>
            @endif

        </div>
    @endforeach

    @if($canManageTables)
    <div x-show="showAddTableModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" @click.self="showAddTableModal = false">
        <form method="POST" action="{{ route('admin.meja.store') }}" class="w-full max-w-sm rounded-xl border-4 border-stone-900 bg-white p-5 shadow-[6px_6px_0_0_rgba(0,0,0,1)]" @click.stop>
            @csrf
            <h2 class="text-lg font-black text-stone-900">Tambah Meja · Lantai <span x-text="addTableFloor"></span></h2>
            <input type="hidden" name="lantai" :value="addTableFloor">
            <label for="new-table-number" class="mt-4 block text-sm font-bold text-stone-700">Nomor meja</label>
            <input id="new-table-number" name="nomor_meja" required maxlength="10" class="mt-1 w-full rounded-lg border-2 border-stone-400 px-3 py-2 font-bold" placeholder="Contoh: M23">
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="showAddTableModal = false" class="rounded-lg border border-stone-400 px-3 py-2 text-sm font-bold">Batal</button>
                <button type="submit" class="rounded-lg border-2 border-stone-900 bg-[#4A2311] px-4 py-2 text-sm font-bold text-white">Tambah meja</button>
            </div>
        </form>
    </div>
    @endif

    <!-- DAFTAR RESERVASI HARI INI -->
    <!-- Tambahkan kondisi && selectedResId agar daftar ini hanya muncul ketika Admin sudah memilih reservasi -->
    <div x-show="isPlotting && selectedResId" style="display: none;" class="mt-8 bg-white rounded-xl border-4 border-stone-900 p-6 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
        <h2 class="font-black text-xl mb-4 text-stone-900">
            📋 Daftar Reservasi & Plot Meja <span class="text-[#4A2311]">(Tanggal: <span x-text="getSelectedDate()"></span>)</span>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <!-- GUNAKAN getSameDateReservations() AGAR HANYA MENAMPILKAN TANGGAL YANG SAMA -->
            <template x-for="res in getSameDateReservations()" :key="res.id">
                <div class="p-4 rounded-xl border-2 transition-all cursor-pointer" 
                     :class="selectedResId == res.id ? 'bg-amber-50 border-amber-500 shadow-[3px_3px_0_0_rgba(245,158,11,1)]' : 'bg-stone-50 border-stone-200 hover:border-stone-400'"
                     @click="selectedResId = res.id; onSelectRes()">
                    
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-bold text-stone-900" x-text="res.customer_nama"></h3>
                        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded border border-stone-300 bg-white text-stone-600" x-text="res.status"></span>
                    </div>

                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-black text-stone-900" x-text="res.jml_org"></h3>
                        <span class="text-xs font-bold text-stone-500 mb-3" x-text="`${res.tgl_formatted} • ${res.jam_mulai} - ${res.jam_selesai}`"></span>
                    </div>
                    {{-- <p class="text-xs font-bold text-stone-500 mb-3" x-text="`${res.tgl_formatted} • ${res.jam_mulai} - ${res.jam_selesai}`"></p> --}}
                    
                    <div class="flex flex-wrap gap-1.5">
                        <template x-if="res.meja_ids && res.meja_ids.length > 0">
                            <template x-for="mId in res.meja_ids">
                                <span class="px-2 py-1 bg-white border-2 border-stone-900 shadow-[2px_2px_0_0_rgba(0,0,0,1)] rounded-md text-[11px] font-black text-[#4A2311]">
                                    <span x-text="`Meja #${ALL_MEJA.find(m => m.id == mId)?.nomor_meja || mId}`"></span>
                                </span>
                            </template>
                        </template>
                        
                        <template x-if="!res.meja_ids || res.meja_ids.length === 0">
                            <span class="text-[11px] font-bold italic text-red-500">Belum di-plot</span>
                        </template>
                    </div>
                </div>
            </template>
            
            <template x-if="getSameDateReservations().length === 0">
                <div class="col-span-full p-4 border-2 border-dashed border-stone-300 rounded-xl text-center text-stone-500 font-bold italic text-sm">
                    Tidak ada reservasi terkonfirmasi untuk tanggal ini.
                </div>
            </template>
            
        </div>
    </div>
</div> <!-- End of x-data wrapper -->



<script>
    const RESERVASI_CONFIRMED = @json($reservasiConfirmed);
    const ALL_MEJA = @json($allMeja);

    document.addEventListener('alpine:init', () => {
        Alpine.data('alokasiMejaApp', () => ({
            currentTab: '{{ $lantaiList->first() ?? 1 }}',
            isPlotting: false,
            showAddTableModal: false,
            addTableFloor: '1',
            selectedResId: '',
            selectedTables: [],
            conflictTables: [],
            reservations: @json($reservasiConfirmed),
            lampStatus: @json($mejaStatusAwal),

            // --- FUNGSI BARU UNTUK FILTER LIST KARTU BAWAH ---
            getSelectedDate() {
                if (!this.selectedResId) return '-';
                const currentRes = this.reservations.find(r => String(r.id) === String(this.selectedResId));
                return currentRes ? currentRes.tgl_formatted : '-';
            },

            getSameDateReservations() {
                if (!this.selectedResId) return [];
                const currentRes = this.reservations.find(r => String(r.id) === String(this.selectedResId));
                if (!currentRes) return [];
                
                // Return HANYA reservasi yang tanggal (Y-m-d) nya sama persis
                return this.reservations.filter(r => r.tanggal === currentRes.tanggal);
            },
            // ------------------------------------------------

            togglePlotting() {
                this.isPlotting = !this.isPlotting;
                if (!this.isPlotting) {
                    this.selectedResId = '';
                    this.selectedTables = [];
                    this.conflictTables = [];
                }
            },

            openAddTable(floor) {
                this.addTableFloor = String(floor);
                this.showAddTableModal = true;
            },

            onSelectRes() {
                this.selectedTables = [];
                this.conflictTables = [];

                const currentRes = this.reservations.find(r => String(r.id) === String(this.selectedResId));
                
                if (currentRes) {
                    this.selectedTables = (currentRes.meja_ids || []).map(String);
                    // Langsung panggil hitungan dari Controller
                    this.conflictTables = (currentRes.conflict_meja_ids || []).map(String);
                }
            },

            getMejaClass(id) {
                const idStr = String(id).trim(); // trim() untuk mencegah error spasi dari blade
                let base = 'bg-white shadow-[4px_4px_0_0_rgba(0,0,0,1)] ';
                const table = ALL_MEJA.find(item => String(item.id) === idStr);

                if (!table?.is_active) {
                    return 'bg-stone-200 border-stone-400 text-stone-400 grayscale cursor-not-allowed shadow-none';
                }
                
                if (!this.isPlotting || !this.selectedResId) return base;

                // Prioritas 1: MERAH (Karena bentrok jam ATAU ada walk-in occupied)
                if (this.conflictTables.includes(idStr) || this.lampStatus[idStr] === 'occupied') {
                    return 'bg-red-100 border-red-600 opacity-60 cursor-not-allowed shadow-none';
                }
                
                // Prioritas 2: KUNING (Meja milik reservasi yang sedang dipilih)
                if (this.selectedTables.includes(idStr)) {
                    return 'bg-amber-100 border-amber-600 shadow-[0_0_20px_rgba(217,119,6,0.3)] ring-4 ring-amber-300 cursor-pointer scale-[1.02]';
                }
                
                // Prioritas 3: HIJAU (Aman digunakan)
                return 'bg-emerald-50 border-emerald-500 cursor-pointer hover:bg-emerald-100 shadow-[4px_4px_0_0_rgba(0,0,0,1)]';
            },

            getLampClass(id) {
                const idStr = String(id).trim();
                const table = ALL_MEJA.find(item => String(item.id) === idStr);
                if (!table?.is_active) return 'bg-stone-400';
                
                if (this.isPlotting && this.selectedResId) {
                    if (this.selectedTables.includes(idStr)) return 'bg-yellow-400';
                    if (this.conflictTables.includes(idStr) || this.lampStatus[idStr] === 'occupied') return 'bg-red-500';
                    return 'bg-green-500';
                }

                const status = this.lampStatus[idStr] || 'available';
                return {
                    'available': 'bg-green-500',
                    'occupied': 'bg-red-500',
                    'reserved': 'bg-yellow-400'
                }[status];
            },

            notify(message) {
                window.dispatchEvent(new CustomEvent('component-alert', {
                    detail: { message }
                }));
            },

            toggleMeja(id) {
                if (!this.isPlotting || !this.selectedResId) return;

                const idStr = String(id).trim();
                const table = ALL_MEJA.find(item => String(item.id) === idStr);
                if (!table?.is_active) {
                    this.notify('Meja nonaktif tidak dapat dialokasikan.');
                    return;
                }

                if (this.conflictTables.includes(idStr) || this.lampStatus[idStr] === 'occupied') {
                    this.notify('Meja ini berpotensi bertabrakan dengan reservasi lain.');
                    return;
                }

                const index = this.selectedTables.indexOf(idStr);

                if (index > -1) {
                    this.selectedTables.splice(index, 1);
                } else {
                    this.selectedTables.push(idStr);
                }
            },

            savePlotting() {
                if (!this.selectedResId) {
                    this.notify('Pilih reservasi terlebih dahulu.');
                    return;
                }
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/meja/${this.selectedResId}/assign-meja`;
                
                const token = document.createElement('input');
                token.type = 'hidden'; token.name = '_token';
                token.value = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                form.appendChild(token);

                this.selectedTables.forEach(id => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden'; inp.name = 'meja_dipilih[]'; inp.value = id;
                    form.appendChild(inp);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }));
    });
</script>

@endsection
