<div x-show="isPelunasanModalOpen"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/70 backdrop-blur-sm"
     style="display: none;"
     x-transition.opacity.duration.300ms>

    <div @click.away="closePelunasanModal()"
         class="w-full max-w-lg bg-white border-4 border-stone-900 rounded-2xl shadow-[8px_8px_0_0_rgba(0,0,0,1)] flex flex-col overflow-hidden transform"
         x-show="isPelunasanModalOpen"
         x-transition.scale.origin.center.duration.300ms>

        <!-- HEADER MODAL -->
        <div class="flex items-center justify-between px-6 py-4 bg-[#4A2311] border-b-4 border-stone-900">
            <h2 class="text-xl font-black tracking-wide text-white">💳 Pelunasan Tagihan</h2>
            <button type="button" @click="closePelunasanModal()" class="text-3xl font-black leading-none text-white transition-colors hover:text-red-400">
                &times;
            </button>
        </div>

        <!-- FORM ACTION MENGGUNAKAN KONKATENASI STRING BIASA (Menghindari error Blade) -->
        <form x-bind:action="'/admin/reservasi/' + pelunasanData.id + '/lunas'" method="POST" class="flex flex-col gap-6 p-6">
            @csrf

            <!-- NAMA CUSTOMER -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-black tracking-wide uppercase text-stone-500">Nama Pelanggan</label>
                <div class="px-4 py-3 text-lg font-bold text-stone-900 bg-stone-100 border-2 border-stone-900 rounded-xl shadow-[2px_2px_0_0_rgba(0,0,0,1)]" 
                     x-text="pelunasanData.nama">
                </div>
            </div>

            <!-- SISA TAGIHAN -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-black tracking-wide text-stone-500 uppercase">Total Harus Dibayar</label>
                <div class="px-4 py-3 text-2xl font-black text-red-700 bg-red-100 border-2 border-red-900 rounded-xl shadow-[4px_4px_0_0_rgba(127,29,29,1)]" 
                     x-text="formatRupiah(pelunasanData.sisa_tagihan)">
                </div>
            </div>

            <!-- METODE PEMBAYARAN (SELECT) -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-black tracking-wide text-stone-500 uppercase">Metode Pembayaran</label>
                <div class="relative">
                    <select
                        name="metode_pelunasan"
                        required
                        x-model="pelunasanData.metode_pelunasan"
                        class="w-full px-4 py-3 text-lg font-bold text-stone-900 bg-white border-2 border-stone-900 rounded-xl"
                    >
                        <option value="">-- Pilih Metode --</option>
                        <option value="tunai">💵 Tunai (Cash)</option>
                        <option value="qris">📱 QRIS / E-Wallet</option>
                        <option value="transfer_bank">🏦 Transfer Bank</option>
                        <option value="debit">💳 Kartu Debit / Kredit</option>
                    </select>
                    {{-- Tampilkan hanya untuk pembayaran tunai --}}
                    <div
                        x-show="pelunasanData.metode_pelunasan === 'tunai'"
                        x-cloak
                        class="mt-4 space-y-3"
                    >
                        <label for="nominal_diterima" class="block text-sm font-black uppercase text-stone-600">
                            Nominal diterima
                        </label>
                        <input
                            id="uang_diterima"
                            name="uang_diterima"
                            type="number"
                            min="0"
                            step="1"
                            x-model.number="pelunasanData.nominal_diterima"
                            x-bind:min="pelunasanData.sisa_tagihan"
                            x-bind:required="pelunasanData.metode_pelunasan === 'tunai'"
                            x-bind:disabled="pelunasanData.metode_pelunasan !== 'tunai'"
                            class="w-full rounded-xl border-2 border-stone-900 p-3 font-bold"
                            placeholder="Masukkan uang yang diterima"
                        >

                        <div class="flex justify-between rounded-lg bg-emerald-50 p-3 font-bold text-emerald-800">
                            <span>Kembalian</span>
                            <span x-text="formatRupiah(Math.max(0, Number(pelunasanData.nominal_diterima || 0) - Number(pelunasanData.sisa_tagihan || 0)))"></span>
                        </div>

                        <p
                            x-show="Number(pelunasanData.nominal_diterima || 0) < Number(pelunasanData.sisa_tagihan || 0)"
                            class="text-sm font-bold text-red-700"
                        >
                            Nominal diterima kurang dari total tagihan.
                        </p>
                    </div>
                    <!-- Panah Dropdown Custom -->
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-stone-900">
                        <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                    </div>
                </div>
            </div>

            <!-- TOMBOL AKSI -->
            <div class="flex items-center justify-end gap-4 pt-6 mt-2 border-t-4 border-dashed border-stone-300">
                <button type="button" @click="closePelunasanModal()" 
                        class="px-6 py-3 font-bold text-stone-700 bg-stone-200 border-2 border-stone-900 rounded-xl shadow-[3px_3px_0_0_rgba(0,0,0,1)] hover:bg-stone-300 active:translate-y-1 active:translate-x-1 active:shadow-none transition-all">
                    Batal
                </button>
                <button
                    type="submit"
                    x-bind:disabled="pelunasanData.metode_pelunasan === 'tunai' && Number(pelunasanData.nominal_diterima || 0) < Number(pelunasanData.sisa_tagihan || 0)"
                    class="px-6 py-3 font-black text-white bg-emerald-500 border-2 border-stone-900 rounded-xl disabled:cursor-not-allowed disabled:opacity-50"
                >
                    ✔ Proses Lunas
                </button>
            </div>
        </form>
    </div>
</div>