<script setup lang="ts">
import { ref, computed } from 'vue'
import { store } from '../../mock/store'
import type { GraphNode } from '../../mock/data'

const selectedType = ref('Tất cả')
const selectedNode = ref<GraphNode>(store.graphNodes[0])

const filteredNodes = computed(() => {
  if (selectedType.value === 'Tất cả') return store.graphNodes
  return store.graphNodes.filter(n => {
    if (selectedType.value === 'Chuyên gia') return n.type === 'person'
    if (selectedType.value === 'Dịch vụ') return n.type === 'service'
    if (selectedType.value === 'Tài liệu') return n.type === 'document'
    return n.type === 'concept'
  })
})

const connectedEdges = computed(() => {
  if (!selectedNode.value) return []
  const id = selectedNode.value.id
  return store.graphEdges.filter(e => e.source === id || e.target === id)
})

function getNode(id: string) {
  return store.graphNodes.find(n => n.id === id)
}

function getNodeColor(type: GraphNode['type']) {
  switch (type) {
    case 'service': return '#2563eb' // blue-600
    case 'person': return '#059669' // emerald-600
    case 'document': return '#d97706' // amber-600
    default: return '#475569' // slate-600
  }
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-4">
    
    <!-- Top Header & Filter -->
    <div class="bg-white border border-slate-200 rounded-md p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-base font-bold text-slate-900 leading-tight">Cây tri thức liên kết (AI Knowledge Graph)</h1>
          <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">Neo4j v2.1</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Trực quan hóa quan hệ giữa các phân hệ, tài liệu và chuyên gia phụ trách</p>
      </div>

      <!-- Type Filter Pills -->
      <div class="flex items-center gap-1 overflow-x-auto">
        <button
          v-for="t in ['Tất cả', 'Dịch vụ', 'Chuyên gia', 'Tài liệu']"
          :key="t"
          type="button"
          class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
          :class="selectedType === t ? 'bg-slate-900 text-white' : 'text-slate-600 bg-slate-100 hover:bg-slate-200'"
          @click="selectedType = t"
        >
          {{ t }}
        </button>
      </div>
    </div>

    <!-- Visualizer Canvas + Details Inspector Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
      
      <!-- GRAPH SVG VIEWER (8 cols) -->
      <div class="lg:col-span-8 bg-white border border-slate-200 rounded-md overflow-hidden p-2 relative min-h-[520px] flex items-center justify-center bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:16px_16px]">
        
        <!-- Legend Indicator -->
        <div class="absolute top-3 left-3 bg-white/95 border border-slate-200 rounded p-2 text-[11px] space-y-1 z-10">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
            <span class="text-slate-700 font-medium">Dịch vụ & Hạ tầng</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
            <span class="text-slate-700 font-medium">Chuyên gia phụ trách</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
            <span class="text-slate-700 font-medium">Tài liệu chuẩn hóa</span>
          </div>
        </div>

        <svg viewBox="0 0 750 500" class="w-full h-[500px]">
          <!-- Draw Edges -->
          <g>
            <line
              v-for="(edge, idx) in store.graphEdges"
              :key="idx"
              :x1="getNode(edge.source)?.x"
              :y1="getNode(edge.source)?.y"
              :x2="getNode(edge.target)?.x"
              :y2="getNode(edge.target)?.y"
              stroke="#cbd5e1"
              stroke-width="1.5"
              stroke-dasharray="3,3"
            />
          </g>

          <!-- Draw Edge Labels -->
          <g>
            <text
              v-for="(edge, idx) in store.graphEdges"
              :key="'txt-' + idx"
              :x="((getNode(edge.source)?.x ?? 0) + (getNode(edge.target)?.x ?? 0)) / 2"
              :y="((getNode(edge.source)?.y ?? 0) + (getNode(edge.target)?.y ?? 0)) / 2 - 4"
              text-anchor="middle"
              fill="#94a3b8"
              font-size="9"
              font-weight="600"
            >
              {{ edge.relation }}
            </text>
          </g>

          <!-- Draw Nodes -->
          <g>
            <g
              v-for="node in filteredNodes"
              :key="node.id"
              class="cursor-pointer transition"
              @click="selectedNode = node"
            >
              <!-- Outer ring if selected -->
              <circle
                v-if="selectedNode?.id === node.id"
                :cx="node.x"
                :cy="node.y"
                r="22"
                fill="none"
                stroke="#0f172a"
                stroke-width="2"
              />

              <!-- Node Circle -->
              <circle
                :cx="node.x"
                :cy="node.y"
                r="16"
                :fill="getNodeColor(node.type)"
              />

              <!-- Label text -->
              <text
                :x="node.x"
                :y="node.y + 28"
                text-anchor="middle"
                fill="#1e293b"
                font-size="11"
                font-weight="700"
              >
                {{ node.label }}
              </text>
            </g>
          </g>
        </svg>

      </div>

      <!-- RIGHT DETAILS INSPECTOR (4 cols) -->
      <div class="lg:col-span-4 bg-white border border-slate-200 rounded-md p-4 space-y-4">
        <h2 class="text-xs font-bold text-slate-900 uppercase pb-2 border-b border-slate-100">
          Thông tin chi tiết thực thể
        </h2>

        <div v-if="selectedNode" class="space-y-3 text-xs">
          <div>
            <span class="text-[10px] text-slate-400 font-semibold uppercase">Tên nút:</span>
            <h3 class="text-sm font-bold text-slate-900 mt-0.5">{{ selectedNode.label }}</h3>
          </div>

          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
              Nhóm: {{ selectedNode.group }}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
              Loại: {{ selectedNode.type }}
            </span>
          </div>

          <div class="p-3 rounded bg-slate-50 border border-slate-100 text-slate-700 leading-relaxed">
            {{ selectedNode.details }}
          </div>

          <!-- Connected Entities -->
          <div class="pt-2 border-t border-slate-100 space-y-2">
            <span class="font-bold text-slate-800 block text-xs">Liên kết trực tiếp ({{ connectedEdges.length }})</span>
            
            <div v-for="(edge, idx) in connectedEdges" :key="idx" class="p-2 rounded bg-slate-50 flex items-center justify-between text-[11px]">
              <span class="font-semibold text-slate-800">
                {{ edge.source === selectedNode.id ? getNode(edge.target)?.label : getNode(edge.source)?.label }}
              </span>
              <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 font-medium">
                {{ edge.relation }}
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>
