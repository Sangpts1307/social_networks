<script setup lang="ts">
import { ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { MOCK_GRAPH_DATA } from '../../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const nodes = ref(MOCK_GRAPH_DATA.nodes)
const links = ref(MOCK_GRAPH_DATA.links)
const selectedNode = ref(nodes.value[0])

function getNode(id: string) {
  return nodes.value.find((n) => n.id === id)
}
</script>

<template>
  <div class="h-screen text-slate-900 antialiased flex flex-col justify-between overflow-hidden bg-[#F8FAFC] font-sans">
    
    <!-- TOP BLUE NAVBAR (MATCH 14-knowledge-graph-explorer.html) -->
    <header class="sticky top-0 z-40 bg-[#1D4ED8] text-white shadow-xs flex-shrink-0">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-8 h-14 flex items-center justify-between gap-4">
        
        <!-- Brand & Sub Nav Links -->
        <div class="flex items-center gap-6 overflow-x-auto">
          <button
            type="button"
            class="flex items-center gap-2 flex-shrink-0 cursor-pointer border-none bg-transparent text-white"
            title="Về cổng Portal"
            @click="emit('back')"
          >
            <div class="w-7 h-7 rounded-full bg-white text-blue-700 flex items-center justify-center font-bold text-xs">
              <span class="material-symbols-outlined text-sm">more_horiz</span>
            </div>
            <span class="text-sm font-extrabold tracking-wider">BASE.VN</span>
          </button>

          <nav class="flex items-center gap-1 text-xs font-semibold text-white/80 whitespace-nowrap">
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'square')">Trang chủ</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'square')">Câu hỏi</button>
            <button type="button" class="px-3 py-4 text-white font-bold border-b-2 border-white bg-transparent cursor-pointer border-t-0 border-l-0 border-r-0">Tri thức</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'library')">Thư viện</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'reward')">Bảng xếp hạng</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'profile')">Cá nhân</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'admin')">Tùy chỉnh</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'search')">Tìm kiếm</button>
          </nav>
        </div>

        <!-- Right User -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <button
            type="button"
            class="p-0.5 rounded-full hover:ring-2 hover:ring-white border-none bg-transparent cursor-pointer"
            title="Hồ sơ cá nhân"
            @click="emit('select-app', 'profile')"
          >
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
          </button>
          <button
            type="button"
            class="p-1 text-white/80 hover:text-white cursor-pointer border-none bg-transparent"
            title="Đăng xuất"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-base">logout</span>
          </button>
        </div>
      </div>
    </header>

    <!-- MAIN GRAPH WORKSPACE (MATCH 14-knowledge-graph-explorer.html) -->
    <main class="max-w-[1440px] mx-auto w-full px-4 lg:px-8 py-3 flex-1 flex gap-4 h-[calc(100vh-61px)] overflow-hidden">
      
      <!-- GRAPH CANVAS -->
      <section class="flex-1 bg-white rounded-lg border border-slate-200 flex flex-col shadow-xs relative overflow-hidden">
        
        <!-- Top Legend & Controls -->
        <div class="p-3.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/60 z-10 text-xs">
          <div class="flex items-center gap-4 font-semibold text-slate-600">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">Loại Node:</span>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-blue-600"></span>
              <span>Chuyên gia</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
              <span>Chủ đề</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-purple-500"></span>
              <span>Danh mục</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
              <span>Tài liệu</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-amber-500"></span>
              <span>Câu hỏi Q&A</span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-slate-400 font-mono text-[11px]">Engine: Neo4j Cypher</span>
            <button
              type="button"
              class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-50 rounded text-slate-700 text-xs font-semibold cursor-pointer"
            >
              Căn giữa đồ thị
            </button>
          </div>
        </div>

        <!-- Interactive SVG Canvas -->
        <div class="flex-1 relative bg-slate-50/30 overflow-auto">
          <svg class="w-full h-full min-h-[460px]" viewBox="0 0 900 480">
            <!-- Grid Lines pattern -->
            <defs>
              <pattern id="grid" width="30" height="30" patternUnits="userSpaceOnUse">
                <path d="M 30 0 L 0 0 0 30" fill="none" stroke="#e2e8f0" stroke-width="0.5" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />

            <!-- Links -->
            <g class="edges">
              <g v-for="(link, idx) in links" :key="idx">
                <line
                  :x1="getNode(link.source)?.x"
                  :y1="getNode(link.source)?.y"
                  :x2="getNode(link.target)?.x"
                  :y2="getNode(link.target)?.y"
                  stroke="#cbd5e1"
                  stroke-width="2"
                  stroke-dasharray="4 2"
                />
                <!-- Label on edge -->
                <text
                  :x="((getNode(link.source)?.x || 0) + (getNode(link.target)?.x || 0)) / 2"
                  :y="((getNode(link.source)?.y || 0) + (getNode(link.target)?.y || 0)) / 2 - 4"
                  fill="#94a3b8"
                  font-size="9"
                  font-weight="bold"
                  text-anchor="middle"
                >
                  {{ link.label }}
                </text>
              </g>
            </g>

            <!-- Nodes -->
            <g class="nodes">
              <g
                v-for="node in nodes"
                :key="node.id"
                class="cursor-pointer transition-transform"
                @click="selectedNode = node"
              >
                <!-- Outer halo on selection -->
                <circle
                  v-if="selectedNode?.id === node.id"
                  :cx="node.x"
                  :cy="node.y"
                  :r="node.r + 6"
                  fill="none"
                  stroke="#3b82f6"
                  stroke-width="2.5"
                  opacity="0.8"
                />
                
                <circle
                  :cx="node.x"
                  :cy="node.y"
                  :r="node.r"
                  :fill="node.color"
                  stroke="#ffffff"
                  stroke-width="2.5"
                  class="shadow-sm"
                />
                
                <text
                  :x="node.x"
                  :y="node.y + node.r + 14"
                  fill="#1e293b"
                  font-size="11"
                  font-weight="bold"
                  text-anchor="middle"
                >
                  {{ node.label }}
                </text>
              </g>
            </g>
          </svg>
        </div>

      </section>

      <!-- RIGHT NODE INSPECTOR -->
      <aside v-if="selectedNode" class="w-80 bg-white rounded-lg border border-slate-200 flex flex-col shadow-xs p-4 space-y-4 flex-shrink-0 text-xs overflow-y-auto">
        <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
          <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider">CHI TIẾT THỰC THỂ</span>
          <span
            class="px-2 py-0.5 rounded text-[10px] font-bold text-white uppercase"
            :style="{ backgroundColor: selectedNode.color }"
          >
            {{ selectedNode.type }}
          </span>
        </div>

        <div class="space-y-2">
          <h2 class="text-sm font-bold text-slate-900 leading-snug">
            {{ selectedNode.label }}
          </h2>
          <p class="text-slate-600 leading-relaxed">
            {{ selectedNode.role }}
          </p>
        </div>

        <div class="space-y-2 pt-2 border-t border-slate-100">
          <span class="font-bold text-slate-500 uppercase text-[10px] tracking-wider block">QUAN HỆ LIÊN KẾT (GRAPH DEGREE)</span>
          <div class="space-y-1.5 font-medium">
            <div
              v-for="(link, i) in links.filter((l) => l.source === selectedNode.id || l.target === selectedNode.id)"
              :key="i"
              class="p-2 rounded bg-slate-50 border border-slate-100 text-[11px] flex items-center justify-between"
            >
              <span class="font-bold text-blue-600">{{ link.label }}</span>
              <span class="text-slate-600">
                {{ link.source === selectedNode.id ? getNode(link.target)?.label : getNode(link.source)?.label }}
              </span>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
          <button
            type="button"
            class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md shadow-xs cursor-pointer border-none text-center"
            @click="emit('select-app', 'square')"
          >
            Khám phá trong Base Square
          </button>
        </div>
      </aside>

    </main>

  </div>
</template>
