<script setup lang="ts">
import { ref, computed } from 'vue'
import { store } from '../../mock/store'

const activeDept = ref('Tất cả')
const searchQuery = ref('')
const showUploadModal = ref(false)

// Form fields
const docTitle = ref('')
const docCategory = ref('Tài liệu hướng dẫn')
const docDepartment = ref('Khối Kỹ thuật')
const docType = ref<'pdf' | 'docx'>('pdf')

const departments = ['Tất cả', 'Khối Kỹ thuật', 'Nhân sự & Văn hóa', 'Pháp chế & An ninh mạng']

const filteredDocs = computed(() => {
  return store.documents.filter(d => {
    if (activeDept.value !== 'Tất cả' && d.department !== activeDept.value) return false
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      return d.title.toLowerCase().includes(q) || d.summary.toLowerCase().includes(q)
    }
    return true
  })
})

function handleDownload(title: string) {
  alert(`Đang tải xuống: ${title}\n(Tài liệu mẫu lưu trữ an toàn trên hạ tầng nội bộ).`)
}

function handleUpload() {
  if (!docTitle.value.trim()) return
  store.addDocument(docTitle.value, docCategory.value, docDepartment.value, docType.value)
  docTitle.value = ''
  showUploadModal.value = false
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-5">
    
    <!-- Top Bar -->
    <div class="bg-white border border-slate-200 rounded-md p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div>
        <h1 class="text-base font-bold text-slate-900 leading-tight">Thư viện tài liệu & Quy trình</h1>
        <p class="text-xs text-slate-500 mt-0.5">Kho văn bản lưu trữ chuẩn hóa đã được trích xuất dữ liệu vào Cây tri thức AI</p>
      </div>

      <div class="flex items-center gap-3">
        <div class="relative w-full sm:w-64">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Tìm tài liệu hoặc quy trình..."
            class="w-full pl-8 pr-3 py-1.5 bg-slate-50 text-xs rounded border border-slate-200 outline-none"
          >
          <span class="material-symbols-outlined absolute left-2.5 top-2 text-slate-400 text-sm">search</span>
        </div>

        <button
          type="button"
          class="px-3.5 py-1.5 rounded bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer border-none flex items-center gap-1.5 transition flex-shrink-0"
          @click="showUploadModal = true"
        >
          <span class="material-symbols-outlined text-sm">upload_file</span>
          <span>Tải tài liệu lên</span>
        </button>
      </div>
    </div>

    <!-- Department Filters -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
      <button
        v-for="dept in departments"
        :key="dept"
        type="button"
        class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
        :class="activeDept === dept ? 'bg-slate-900 text-white' : 'text-slate-600 bg-white border border-slate-200 hover:bg-slate-50'"
        @click="activeDept = dept"
      >
        {{ dept }}
      </button>
    </div>

    <!-- Documents Grid / Table -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="doc in filteredDocs"
        :key="doc.id"
        class="bg-white border border-slate-200 rounded-md p-4 flex flex-col justify-between space-y-3"
      >
        <div>
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
              <span 
                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                :class="doc.fileType === 'pdf' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700'"
              >
                {{ doc.fileType }}
              </span>
              <span class="text-[11px] text-slate-500">{{ doc.department }}</span>
            </div>

            <span 
              v-if="doc.isAiIndexed" 
              class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
              title="Đã được AI trích xuất thực thể vào đồ thị tri thức"
            >
              <span class="material-symbols-outlined text-xs">auto_awesome</span>
              <span>AI Indexed</span>
            </span>
          </div>

          <h3 class="font-bold text-slate-900 text-sm mt-2 leading-snug">{{ doc.title }}</h3>
          <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ doc.summary }}</p>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <div class="space-x-2 text-[11px]">
            <span>{{ doc.fileSize }}</span>
            <span>•</span>
            <span>{{ doc.downloads }} lượt tải</span>
            <span>•</span>
            <span>{{ doc.createdAt }}</span>
          </div>

          <button
            type="button"
            class="px-3 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold cursor-pointer border-none flex items-center gap-1 transition"
            @click="handleDownload(doc.title)"
          >
            <span class="material-symbols-outlined text-xs">download</span>
            <span>Tải về</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Upload Document Modal -->
    <div v-if="showUploadModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
      <div class="bg-white border border-slate-200 rounded-md max-w-md w-full p-5 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="text-sm font-bold text-slate-900">Tải tài liệu mới lên thư viện</h3>
          <button 
            type="button" 
            class="text-slate-400 hover:text-slate-700 cursor-pointer border-none bg-transparent text-sm p-0"
            @click="showUploadModal = false"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold text-slate-800 mb-1">Tên tài liệu / Văn bản</label>
            <input 
              v-model="docTitle" 
              type="text" 
              placeholder="VD: Hướng dẫn tích hợp SSO 2026..." 
              class="w-full p-2 bg-slate-50 border border-slate-200 rounded outline-none"
            >
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block font-semibold text-slate-800 mb-1">Phòng ban</label>
              <select v-model="docDepartment" class="w-full p-2 bg-slate-50 border border-slate-200 rounded outline-none">
                <option value="Khối Kỹ thuật">Khối Kỹ thuật</option>
                <option value="Nhân sự & Văn hóa">Nhân sự & Văn hóa</option>
                <option value="Pháp chế & An ninh mạng">Pháp chế</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-slate-800 mb-1">Định dạng file</label>
              <select v-model="docType" class="w-full p-2 bg-slate-50 border border-slate-200 rounded outline-none">
                <option value="pdf">Tệp PDF (.pdf)</option>
                <option value="docx">Tệp Word (.docx)</option>
              </select>
            </div>
          </div>

          <div class="p-3 bg-slate-50 rounded border border-dashed border-slate-300 text-center text-slate-500">
            <span class="material-symbols-outlined text-2xl text-slate-400">cloud_upload</span>
            <p class="mt-1">Kéo thả tệp hoặc chọn từ máy tính</p>
            <span class="text-[10px] text-slate-400">Hỗ trợ PDF, DOCX (Tối đa 25MB)</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button 
            type="button" 
            class="px-3 py-1.5 rounded bg-slate-100 text-slate-700 text-xs font-semibold cursor-pointer border-none"
            @click="showUploadModal = false"
          >
            Hủy
          </button>
          <button 
            type="button" 
            class="px-4 py-1.5 rounded bg-slate-900 text-white text-xs font-semibold cursor-pointer border-none"
            @click="handleUpload"
          >
            Tải lên & Tự động Index AI
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
