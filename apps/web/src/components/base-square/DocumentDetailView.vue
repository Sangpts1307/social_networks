<script setup lang="ts">
import { ref } from 'vue'
import type { KnowledgeItem } from '../../api/learning'
import { learningApi } from '../../api/learning'
import { ApiError } from '../../api/auth'

const props = defineProps<{ item: KnowledgeItem; token: string }>()
const emit = defineEmits<{ (event: 'back'): void; (event: 'refresh'): void; (event: 'ask'): void }>()
const downloading = ref(false)
const downloadError = ref('')

function formattedDate(value: string): string {
  if (!value) return 'Vừa cập nhật'
  return new Date(value).toLocaleString('vi-VN', { dateStyle: 'medium' })
}

async function download(): Promise<void> {
  downloading.value = true
  downloadError.value = ''
  try {
    const { data } = await learningApi.documentDownload(props.token, props.item.id)
    window.open(data.url, '_blank', 'noopener,noreferrer')
  } catch (error) {
    downloadError.value = error instanceof ApiError ? error.message : 'Chưa thể tạo đường dẫn tải tệp.'
  } finally {
    downloading.value = false
  }
}
</script>

<template>
  <div class="w-full bg-[#F8FAFC] py-6 flex-1">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-8">
      
      <!-- Back button -->
      <button
        type="button"
        class="mb-4 flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:underline border-none bg-transparent cursor-pointer p-0"
        @click="emit('back')"
      >
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        <span>Quay lại thư viện tài liệu</span>
      </button>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT & CENTER: DOCUMENT HEADER & PDF PREVIEW (CHUẨN 08b-learning-detail.html) -->
        <section class="lg:col-span-9 space-y-4">
          
          <!-- Top Title Bar with Vote & Edit Button -->
          <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
              <!-- Vote box -->
              <div class="flex flex-col items-center justify-center p-2 bg-emerald-50 text-emerald-700 rounded-md border border-emerald-200 flex-shrink-0">
                <span class="text-xl font-bold">1</span>
                <span class="text-[9px] font-extrabold uppercase tracking-wider">✔ VOTED</span>
              </div>

              <!-- Document Meta -->
              <div class="space-y-0.5">
                <h1 class="text-lg font-bold text-slate-900">{{ item.title }}</h1>
                <p class="text-xs text-slate-500">
                  Phiên bản: <strong class="text-slate-700">{{ item.current_version_id || 'document.pdf' }}</strong>
                </p>
                <p class="text-[11px] text-slate-400">
                  Chủ đề: <span class="text-slate-600 font-medium">{{ item.topic_name }}</span>
                  <span v-if="item.category_name"> › {{ item.category_name }}</span>
                  • Xuất bản {{ formattedDate(item.published_at) }}
                </p>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button
                type="button"
                :disabled="downloading"
                class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-md shadow-xs border-none cursor-pointer transition disabled:opacity-50"
                @click="download"
              >
                {{ downloading ? 'Đang tạo liên kết...' : 'Tải tệp private' }}
              </button>
            </div>
          </div>

          <!-- File Metadata Strip -->
          <div class="flex items-center justify-between px-2 text-xs text-slate-600">
            <div class="flex items-center gap-3">
              <span class="font-bold text-slate-800">Tài liệu đã lập chỉ mục AI</span>
              <span class="text-slate-400">Provenance đã xác thực • Sẵn sàng cho Knowledge Graph</span>
            </div>
            <div class="flex items-center gap-3 font-semibold text-blue-600">
              <button
                type="button"
                class="hover:underline flex items-center gap-1 border-none bg-transparent cursor-pointer text-blue-600 p-0 text-xs font-semibold"
                @click="download"
              >
                <span class="material-symbols-outlined text-sm">download</span> Tải xuống
              </button>
            </div>
          </div>

          <p
            v-if="downloadError"
            role="alert"
            class="border border-rose-200 bg-rose-50 p-3 text-xs font-medium text-rose-700 rounded-md"
          >
            {{ downloadError }}
          </p>

          <!-- PDF VIEWER WORKSPACE (CHUẨN 08b-learning-detail.html) -->
          <div class="bg-slate-900 rounded-lg overflow-hidden border border-slate-800 shadow-md">
            
            <!-- PDF Dark Toolbar -->
            <div class="h-10 bg-[#323639] text-white px-4 flex items-center justify-between text-xs border-b border-white/10">
              <div class="flex items-center gap-3">
                <button type="button" class="p-1 hover:bg-white/10 rounded border-none bg-transparent text-white cursor-pointer">
                  <span class="material-symbols-outlined text-base">menu</span>
                </button>
                <span class="font-semibold text-white/90 truncate max-w-xs">{{ item.title }}</span>
              </div>
              
              <div class="flex items-center gap-4 text-white/70">
                <span class="cursor-pointer hover:text-white">−</span>
                <span class="text-[11px] font-mono">100%</span>
                <span class="cursor-pointer hover:text-white">+</span>
                <button
                  type="button"
                  class="p-1 hover:text-white border-none bg-transparent text-white/70 cursor-pointer"
                  title="Tải tệp"
                  @click="download"
                >
                  <span class="material-symbols-outlined text-base">download</span>
                </button>
                <span class="cursor-pointer hover:text-white material-symbols-outlined text-base">print</span>
              </div>
            </div>

            <!-- PDF Viewer Content Mock Canvas -->
            <div class="grid min-h-[460px] place-items-center p-8 bg-[#525659]">
              <article class="flex min-h-[340px] w-full max-w-2xl flex-col justify-between border border-cyan-400/30 bg-[#071e3d] p-8 text-white rounded shadow-lg">
                <p class="text-xs font-bold tracking-[.25em] text-cyan-300">
                  BASE SQUARE / KNOWLEDGE DOCUMENT
                </p>
                <div>
                  <h2 class="text-2xl md:text-3xl font-extrabold leading-tight">{{ item.title }}</h2>
                  <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-300">
                    {{ item.summary || 'Tài liệu hướng dẫn chuyên môn được lưu trữ an toàn trong kho lưu trữ Base Square.' }}
                  </p>
                  <p class="mt-4 text-xs text-cyan-100 bg-cyan-900/40 p-2.5 rounded border border-cyan-500/30">
                    Tệp gốc được lưu trữ trong Object Storage an toàn. Chọn "Tải tệp private" để tải bản đầy đủ có chữ ký bảo mật.
                  </p>
                </div>
                <p class="border-t border-white/20 pt-4 text-[11px] text-white/60 font-mono">
                  PROVENANCE · {{ item.published_version_id || item.current_version_id || item.id }}
                </p>
              </article>
            </div>

          </div>

        </section>

        <!-- RIGHT SIDEBAR (CHUẨN 08b-learning-detail.html) -->
        <aside class="space-y-4 lg:col-span-3 text-xs">
          
          <section class="border border-slate-200 bg-white p-4 rounded-lg shadow-xs">
            <h3 class="font-bold text-slate-900">Danh mục</h3>
            <p class="mt-2 font-semibold text-blue-600">
              {{ item.topic_name }}
              <span v-if="item.category_name"> / {{ item.category_name }}</span>
            </p>
          </section>

          <section class="border border-slate-200 bg-white p-4 rounded-lg shadow-xs">
            <h3 class="font-bold text-slate-900">Phiên bản đã xuất bản</h3>
            <div class="mt-3 flex gap-3 border border-slate-200 bg-slate-50 p-3 rounded">
              <span class="grid size-8 place-items-center bg-rose-600 text-[10px] font-bold text-white rounded">
                DOC
              </span>
              <div>
                <p class="font-bold text-slate-900 text-xs truncate">Bản hiện hành</p>
                <p class="mt-0.5 text-[11px] text-slate-500">{{ formattedDate(item.published_at) }}</p>
              </div>
            </div>
          </section>

          <section class="border border-slate-200 bg-white p-4 rounded-lg shadow-xs">
            <h3 class="font-bold text-slate-900">Nguồn Graph Provenance</h3>
            <p class="mt-2 break-all font-mono text-[10px] text-slate-500 bg-slate-50 p-2 rounded border border-slate-100">
              {{ item.id }}
            </p>
            <p class="mt-3 rounded bg-amber-50 p-2.5 leading-relaxed text-amber-800 text-[11px] border border-amber-200">
              Knowledge Graph chỉ tạo projection từ tài liệu đã publish có provenance rõ ràng; AI không tự ghi dữ liệu vào graph.
            </p>
          </section>

        </aside>

      </div>

    </div>
  </div>
</template>
