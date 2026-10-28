// Isolated design preview. Production entry and API are unchanged.
import { createApp, h } from 'vue'
import './src/style.css'
import Login from './src/views/LoginView.vue'
import ChangePassword from './src/views/ChangePasswordView.vue'
import Welcome from './src/views/WelcomeView.vue'
import Inside from './src/views/modules/BaseInsideView.vue'
import Message from './src/views/modules/BaseMessageView.vue'
import Admin from './src/views/modules/BaseAdminView.vue'
import Learning from './src/views/LearningView.vue'
import { MOCK_QA_QUESTIONS, MOCK_LIBRARY_DOCUMENTS } from './src/mock/mockData.js'

const user = {id:'user-1', organization_id:'design-preview', email:'sang.pham@openhello.vn', display_name:'Sang Phạm',must_change_password:false}
const topics = [{id:'tech',code:'TECH',name:'Kỹ thuật',description:null,categories:[{id:'backend',topic_id:'tech',parent_id:null,code:'BACKEND',name:'Backend / AI',description:null}]},{id:'hr',code:'HR',name:'HR & Văn hóa',description:null,categories:[]}]
const items = MOCK_LIBRARY_DOCUMENTS.map((d:any)=>({...d,topic_id:'tech',category_id:'backend',summary:'Tài liệu chuyên môn trong hệ sinh thái quản trị tri thức doanh nghiệp.',topic_name:d.category,category_name:'Backend / AI',published_at:'2026-09-15T09:00:00+07:00',current_version_id:'v1.0',published_version_id:'v1.0'}))
const questions = MOCK_QA_QUESTIONS.map((q:any)=>({...q,topic_id:'tech',category_id:'backend',author_user_id:'user-1',body_plaintext:'Trao đổi kinh nghiệm và chia sẻ kiến thức cùng đồng nghiệp trong doanh nghiệp.',question_status:'open',answer_count:q.answersCount,score:q.votes,created_at:'2026-09-15T09:00:00+07:00'}))
const question = {...questions[0],best_answer_id:'answer-1',answers:[{id:'answer-1',question_id:'q-1',author_user_id:'user-2',body_markdown:'Knowledge Graph liên kết tài liệu, chuyên gia và chủ đề để truy xuất thông tin theo ngữ cảnh.',body_plaintext:'Knowledge Graph liên kết tài liệu, chuyên gia và chủ đề để truy xuất thông tin theo ngữ cảnh.',score:12,created_at:'2026-09-15T10:00:00+07:00',edited_at:null}]}
const originalFetch = window.fetch.bind(window)
window.fetch = async (input:any, init?:any) => {
  const url = String(input)
  if (!url.includes('/api/')) return originalFetch(input,init)
  let payload:any = {data:[]}
  if(url.includes('/topics')) payload={data:topics}
  else if(url.includes('/knowledge-items')) payload={data:items}
  else if(url.includes('/questions/')) payload={data:question}
  else if(url.includes('/questions')) payload={data:questions,meta:{next_cursor:null}}
  return new Response(JSON.stringify(payload),{status:200,headers:{'Content-Type':'application/json'}})
}
const group = new URLSearchParams(location.search).get('group') || 'all'
const specs:any[] = [
 ['01 — Đăng nhập',Login,{}],
 ['02 — Đổi mật khẩu lần đầu',ChangePassword,{user:{...user,must_change_password:true},token:'design-preview'}],
 ['03 — Cổng ứng dụng',Welcome,{user}],
 ['04 — Base Inside / Bảng tin',Inside,{user}],
 ['05 — Base Inside / Soạn bài viết',Inside,{user},{isComposerExpanded:true}],
 ['06 — Base Message / Tất cả',Message,{user}],
 ['07 — Base Message / Nhóm',Message,{user},{activeTab:'group'}],
 ['08 — Quản trị kiểm duyệt',Admin,{user}],
 ...[['questions','09 — Hỏi đáp Q&A'],['knowledge','10 — Knowledge Graph'],['library','11 — Thư viện tài liệu'],['reward','12 — Bảng xếp hạng'],['profile','13 — Hồ sơ cá nhân'],['admin','14 — Quản trị tri thức'],['search','15 — Tìm kiếm AI']].map(([tab,name])=>[name,Learning,{token:'design-preview',user,initialTab:tab}]),
 ['16 — Đặt câu hỏi',Learning,{token:'design-preview',user,initialTab:'questions'},{showQuestionComposer:true}],
 ['17 — Chi tiết câu hỏi',Learning,{token:'design-preview',user,initialTab:'questions'},{selectedQuestion:question,showQuestionDetail:true}],
 ['18 — Chi tiết tài liệu',Learning,{token:'design-preview',user,initialTab:'library'},{selectedItem:items[0],showDocumentDetail:true}],
 ['19 — Đăng nhập / Thông báo lỗi',Login,{notice:'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'}]
]
const selected = group==='a' ? specs.slice(0,8) : group==='b' ? specs.slice(8) : specs
createApp({render:()=>h('main',selected.map(([name,Component,props,state])=>h('section',{'class':'figma-screen','data-screen':name},[h('h2',name),h('div',[h(Component,{...props,ref:(vm:any)=>{if(vm&&state)setTimeout(()=>Object.assign(vm.$.setupState,state),700)}})])])))}).mount('#app')
setTimeout(async()=>{const s=document.createElement('script');s.src='/figma-prepare.js';s.onload=()=>{(window as any).prepareFigmaCapture()};document.head.appendChild(s)},2200)
