<template>
  <div class="min-h-screen bg-gray-100 p-8 font-sans">
    <div class="max-w-4xl mx-auto">
      
      <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">
        🚀 Daftar Produk (Vue + Laravel)
      </h1>

      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row gap-4 justify-between items-center">
          <div class="w-full md:w-1/2">
            <input 
              v-model="params.keyword" 
              type="text" 
              placeholder="Cari nama produk..." 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition"
            >
          </div>

          <select v-model="params.sort_dir" class="px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none cursor-pointer">
            <option value="asc">Harga: Termurah</option>
            <option value="desc">Harga: Termahal</option>
          </select>
        </div>

        <div v-if="loading" class="p-10 text-center text-blue-600 font-semibold animate-pulse bg-blue-50">
          🔄 Sedang menghubungkan ke Laravel...
        </div>

        <div v-else-if="errorMsg" class="p-10 text-center text-red-500 bg-red-50">
          <p class="font-bold">Gagal mengambil data!</p>
          <p class="text-sm">{{ errorMsg }}</p>
          <p class="text-xs mt-2 text-gray-600">Pastikan server Laravel jalan di port 8000</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-blue-600 text-white uppercase text-sm">
              <tr>
                <th class="p-4">No</th>
                <th class="p-4">Nama Produk</th>
                <th class="p-4">Kategori</th>
                <th class="p-4 text-right">Harga (Rp)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, index) in produkList" :key="item.id" class="hover:bg-blue-50 transition duration-150">
                <td class="p-4 text-gray-500 font-mono">{{ params.start + index + 1 }}</td>
                <td class="p-4 font-bold text-gray-800">{{ item.nama_produk }}</td>
                <td class="p-4 text-sm">
                  <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs font-semibold uppercase tracking-wide">
                    {{ item.kategori }}
                  </span>
                </td>
                <td class="p-4 text-right font-bold text-green-600 tracking-wide">
                  {{ formatUang(item.harga) }}
                </td>
              </tr>
              
              <tr v-if="produkList.length === 0">
                <td colspan="4" class="p-10 text-center text-gray-400 italic">
                  Tidak ada produk ditemukan dengan kata kunci "{{ params.keyword }}"
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
          <button 
            @click="gantiHalaman('prev')" 
            :disabled="halamanKe === 1 || loading"
            class="px-4 py-2 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed font-medium text-gray-700 transition"
          >
            &larr; Prev
          </button>

          <span class="text-sm text-gray-600 font-semibold bg-white px-3 py-1 rounded border">
            Halaman {{ halamanKe }}
          </span>

          <button 
            @click="gantiHalaman('next')" 
            :disabled="!adaLanjut || loading"
            class="px-4 py-2 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed font-medium text-gray-700 transition"
          >
            Next &rarr;
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue';
import axios from 'axios';

// --- KONFIGURASI ---
// Pastikan Port 8000 sesuai dengan terminal Laravel kamu
const API_URL = 'http://127.0.0.1:8000/api/produk/list'; 

// --- STATE (Variable Data) ---
const produkList = ref([]);
const loading = ref(false);
const errorMsg = ref('');
const totalData = ref(0);

const params = reactive({
  keyword: '',
  sort_by: 'harga',
  sort_dir: 'asc',
  start: 0,
  limit: 5
});

// --- COMPUTED (Logika Otomatis) ---
const halamanKe = computed(() => (params.start / params.limit) + 1);
const adaLanjut = computed(() => (params.start + params.limit) < totalData.value);

// --- METHODS (Fungsi) ---
const ambilData = async () => {
  loading.value = true;
  errorMsg.value = ''; // Reset error
  
  try {
    const response = await axios.post(API_URL, params);
    produkList.value = response.data.produk;
    totalData.value = response.data.count;
  } catch (error) {
    console.error("Error Detail:", error);
    errorMsg.value = "Backend Laravel tidak merespon (Cek Terminal Port 8000)";
  } finally {
    loading.value = false;
  }
};

const gantiHalaman = (arah) => {
  if (arah === 'next') params.start += params.limit;
  else if (arah === 'prev' && params.start > 0) params.start -= params.limit;
};

const formatUang = (angka) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(angka);
};

// --- WATCH & MOUNT ---
// Jika user mengetik atau ganti sort, otomatis ambil data baru
watch(() => params.keyword, () => {
  // Jika user mengetik pencarian baru...
  if (params.start !== 0) {
    // Kalau user SEDANG BUKAN di halaman 1, lempar paksa ke halaman 1.
    // Perubahan ini akan otomatis memicu watcher ke-2 (di bawah), jadi data terambil.
    params.start = 0;
  } else {
    // Kalau user SUDAH di halaman 1, langsung ambil data baru.
    ambilData();
  }
});

// 2. Pantau khusus Sorting & Ganti Halaman
// Fungsi ini jalan kalau params.start berubah (gara-gara search di atas)
// Atau kalau user ganti dropdown sort.
watch(() => [params.start, params.sort_dir], () => {
  ambilData();
});

// Jalankan saat pertama kali dibuka
onMounted(() => {
  ambilData();
});
</script>