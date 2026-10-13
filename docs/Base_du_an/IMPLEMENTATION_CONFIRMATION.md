# Xác nhận trước khi bắt đầu triển khai

> **Mục đích:** Bạn trả lời các mục trong file này để chốt phạm vi và các quyết định có ảnh hưởng đến code/database. Sau khi chốt, đây sẽ là đầu vào để tạo skeleton, migration, API contract, frontend và test.  
> **Cách trả lời:** điền ngay dưới từng mã `CONF-xx` hoặc gửi lại phần “Mẫu trả lời nhanh” ở cuối file. Nếu đồng ý phương án khuyến nghị, chỉ cần ghi `CONF-xx: đồng ý`.
>
> **Trạng thái: ĐÃ CHỐT — 2026-09-12.** Phần trả lời nguyên gốc bên dưới được giữ làm nguồn quyết định. Bản chuẩn hóa có hiệu lực khi code nằm tại [IMPLEMENTATION_BASELINE.md](IMPLEMENTATION_BASELINE.md).

---

## A. Những gì đã chốt

Các mục dưới đây được xem là đã duyệt từ phản hồi của bạn. Hãy sửa trực tiếp nếu có thay đổi.

| Mã | Quyết định đã chốt |
|---|---|
| FIX-01 | Identity dùng bảng `users` cho credential: password hash, password version, lock state và metadata đăng nhập. Không tạo `password_credentials` hay `auth_identities` riêng. |
| FIX-02 | `user_profiles` chỉ giữ thông tin hồ sơ/PII như tên, điện thoại, ngày sinh, địa chỉ và avatar. |
| FIX-03 | Không triển khai OTP và không có bảng activation/challenge riêng. |
| FIX-04 | Social không có post Poll/Vote. Không tạo `post_polls`, `poll_options`, `poll_votes`; `post_type` chỉ gồm update/news/announcement/event. |
| FIX-05 | Mỗi service sở hữu database của mình; không dùng foreign key hoặc SQL join xuyên database. |

---

## B. Bắt buộc xác nhận trước khi code Phase 1

### 1. Phạm vi lần triển khai đầu

**CONF-01 — Modules MVP**

Khuyến nghị triển khai theo thứ tự: 

1. Identity + RBAC cơ bản + Admin quản lý user/phòng ban.
2. Social: channel, post, attachment, comment, reaction, save.
3. Learning: topic, Q&A, answer, vote, best answer, document library.
4. Notification in-app.
5. Chat direct/group cơ bản.
6. AI Search/Knowledge Graph sau khi dữ liệu Q&A/tài liệu đã có.

Hãy xác nhận các module làm ở đợt đầu và module hoãn lại:

```text
CONF-01:
Tôi nghĩ sẽ cần đẩy lên trước phần Identity + RBAC cơ bản + Admin quản lý user/phòng ban. và demo 1 chút về phần Cây tri thức để có luồng ổn đã.
Sau đó nếu AI khả thi, thì sẽ làm theo thứ tự
```

**CONF-02 — Ứng dụng sẽ làm trước**

Khuyến nghị: triển khai **Web Admin + Web người dùng** trước; Android chỉ triển khai khi API ổn định.

```text
CONF-02: Web trước
```

### 2. Kiến trúc và công nghệ

**CONF-03 — Kiến trúc triển khai**

Khuyến nghị: giữ ranh giới service như tài liệu (`Identity`, `Social`, `Learning`, `Chat`, `Notification`, `AI Knowledge`), nhưng chỉ khởi tạo những service thuộc MVP. Gateway/BFF là điểm vào duy nhất cho frontend.

```text
CONF-03: đồng ý
```

**CONF-04 — Stack backend, frontend và mobile**

Repository hiện chưa có source code/migration để suy ra stack đã chốt. Cần chọn chính xác để tạo skeleton đúng ngay từ đầu.

```text
CONF-04:
Backend Identity/Social/Learning/Notification: laravel
Backend Chat: laravel
Frontend Web: vue.js
Mobile Android: cái này kotlin hoặc react native hoặc flutter tùy
API style:  REST + WebSocket cho chat
```

**CONF-05 — Hạ tầng môi trường dev**

Khuyến nghị cho local development: Docker Compose với MySQL 8, MongoDB, Redis, MinIO; Neo4j chỉ bật khi bắt đầu AI/KG. Chưa cần cloud production ở phase đầu.

```text
CONF-05: Docker Compose local / cài DB local / môi trường khác: cái này cứ như dự kiến đã, tôi sẽ cung cấp khi code
```

### 3. Identity, account và quyền

**CONF-06 — Cách tạo tài khoản**

Vì không dùng OTP, cần chọn một flow rõ ràng. Khuyến nghị: **Admin import/tạo tài khoản → người dùng đăng nhập bằng mã nhân viên hoặc email và mật khẩu do Admin cấp → bắt buộc đổi mật khẩu lần đầu**. Không tự đăng ký công khai.

```text
CONF-06: đồng ý 
```

**CONF-07 — Định danh đăng nhập**

Khuyến nghị: cho phép đăng nhập bằng `employee_code` **hoặc** corporate email; password bắt buộc; account tự khóa tạm thời sau 5 lần sai liên tiếp.

```text
CONF-07:
Login bằng: email 
Khóa sau: 10 lần sai, đến khi được mở bởi admin; mở khóa bằng: Admin
```

**CONF-08 — Roles của MVP**

Khuyến nghị role ban đầu: `super_admin`, `hr_manager`, `team_leader`, `qa_expert`, `member`. Cần xác nhận role nào có quyền: quản lý user/phòng ban, đăng news/announcement, ghim/xóa post, duyệt tài liệu, chọn best answer, xem audit.

```text
CONF-08: dùng danh sách trên: cứ trể super_admin max quyền, hr_manager sẽ có quyền liên quan đến nhân sự
```

**CONF-09 — Phòng ban và nhân sự**

Khuyến nghị: một người có một phòng ban chính và có thể thuộc thêm nhiều team; phòng ban dùng cây cha-con không giới hạn cố định.

```text
CONF-09: đồng ý
```

---

## C. Chốt trước khi triển khai từng module

### 4. Social / Inside

**CONF-10 — Channel và quyền đăng bài**

Khuyến nghị: có channel toàn công ty, channel phòng ban, channel dự án/custom; thành viên chỉ xem/đăng theo membership; chỉ role được cấp quyền mới đăng announcement, ghim hoặc moderation.

```text
CONF-10: đồng ý 
```

**CONF-11 — Social feature scope**

Đánh dấu các chức năng cần làm ngay:

```text
CONF-11:
Post text: có
Attachment ảnh/file: có
Event post/RSVP: có
Comment: có; reply comment: có
Reaction: có/; loại reaction: like, tym, sad, phẫn nộ
Save post: có
Follow user: có/
Report/moderation: có/
```

**CONF-12 — Quy tắc xóa và duyệt post**

Khuyến nghị: post bình thường publish ngay; announcement/news có thể yêu cầu role đặc biệt; xóa là soft delete/ẩn, vẫn giữ audit; file phải scan trước khi public.

```text
CONF-12: đồng ý 
```

### 5. Learning / Base Square

**CONF-13 — Q&A**

Khuyến nghị: user hỏi theo topic/category; mọi user được answer; author của question hoặc `qa_expert` chọn một best answer; vote chỉ `+1` trong MVP; comment dưới question/answer chỉ làm nếu cần.

```text
CONF-13:
Ai được chọn best answer: sẽ tự tính câu trl nhiều like nhất
Vote: có downvote
Comment Q&A: có
Ẩn danh question: không
```

**CONF-14 — Point, coin và bounty**

Khuyến nghị: làm point leaderboard trước. Coin/bounty chỉ triển khai khi bạn xác nhận coin không thể đổi thành tiền/quà ngoài hệ thống và có rule cộng-trừ cụ thể.

```text
CONF-14:
Point leaderboard: có (làm sau)
Coin: có; dùng để: quy đổi tương đương tiền thưởng
Bounty câu hỏi: có
Rule điểm ban đầu: hỏi ..., trả lời ..., best answer ...
```

**CONF-15 — Tài liệu và phân quyền**

Khuyến nghị: tài liệu có version; binary ở object storage; access level gồm `organization`, `org_unit`, `role`, `user`; tài liệu confidential không vào AI/Search nếu chưa duyệt.

```text
CONF-15:
Ai upload: những người với role leader trở lên
Ai duyệt publish: super_admin
Phân quyền tài liệu cần có: ai cũng được xem hết
Giới hạn file (type, dung lượng): tính sau, cứ để tạm trước
```

**CONF-16 — Course/Lesson**

UI hiện có tín hiệu về course nhưng chưa có flow authoring/quiz/certificate đầy đủ. Khuyến nghị hoãn course sang phase sau Q&A và document library.

```text
CONF-16: hoãn cái này, chỉ demo phần cây tri thức trước thôi đã
```

### 6. Chat và Notification

**CONF-17 — Chat scope**

Khuyến nghị MVP: direct chat và group chat, message text + attachment, unread/read, cursor pagination; chưa có call/video, message reaction, edit history hay project domain riêng.

```text
CONF-17:
Direct chat: có/
Group chat: có/
Attachment: có/; loại/dung lượng: phù hợp là được
Edit/delete message: /không
Lưu lịch sử chat: thì vẫn lưu mà
```

**CONF-18 — Notification**

Khuyến nghị MVP: in-app notification trước; gửi khi comment/answer/mention/document được duyệt/chat message; email/push làm sau khi có SMTP/FCM thật.

```text
CONF-18:
In-app: có/
Email: /không
Push: có/
Những event cần gửi notification: như bên trên, và thêm announcement của cty, tạm thời thế
```

### 7. AI, Search và Knowledge Graph

**CONF-19 — Phạm vi dữ liệu AI được phép dùng**

Khuyến nghị: chỉ index Q&A và tài liệu đã publish, có quyền đọc; không index chat, draft, tài liệu restricted/confidential; AI risk scan chỉ tạo đề xuất moderation, không tự xóa nội dung.

```text
CONF-19:
như đề nghị
```

**CONF-20 — AI phase và nhà cung cấp**

Khuyến nghị: hoãn AI/KG đến khi Q&A/document có dữ liệu. Khi triển khai, cần chọn provider/model, region, budget và có chấp thuận về dữ liệu gửi ra ngoài.

```text
CONF-20: hoãn / làm ngay
cái này cần triển khai demo để ra được luồng hđ của cây tri thức liên kết với usser, tài liệu, tri thức,,,, như nào
```

---

## D. Chính sách dữ liệu và vận hành

**CONF-21 — Dữ liệu nhạy cảm và retention**

```text
CONF-21:
Ai được xem profile PII: ...
Ai được xem audit log: super_admin
Chat lưu trong: db 
Tài liệu/file bị xóa có thể khôi phục trong: xóa thì có soft_delete, admin có thể kích hoạt lại
Admin có được export user/document/audit: /không; nếu có thì sửa sau
```

**CONF-22 — Môi trường deploy**

Không bắt buộc để chạy local, nhưng phải có trước production.

```text
CONF-22:
Môi trường cần có: dev trước trên local, sau đó deploy lên production
Nơi deploy dự kiến: aws hoặc server bình thường
Domain/SSL: chưa có
Object storage: cứ lưu local trước
Email SMTP và Push FCM: có sẵn key, cần tích hợp vào code
```

**CONF-23 — Cách nghiệm thu**

Khuyến nghị: mỗi module chỉ được coi là xong khi có migration, API docs, unit/integration test, UI test luồng chính, authorization negative test và Docker/local run guide.

```text
CONF-23: đồng ý
```

---

## E. Mẫu trả lời nhanh

Bạn không cần viết lại câu hỏi. Có thể copy mẫu này, điền phần sau dấu `:` và để `đồng ý` tại các mục theo khuyến nghị.

```text
CONF-01:
CONF-02:
CONF-03:
CONF-04:
CONF-05:
CONF-06:
CONF-07:
CONF-08:
CONF-09:
CONF-10:
CONF-11:
CONF-12:
CONF-13:
CONF-14:
CONF-15:
CONF-16:
CONF-17:
CONF-18:
CONF-19:
CONF-20:
CONF-21:
CONF-22:
CONF-23:
```

## F. Điều kiện để bắt đầu code

Có thể bắt đầu phần Identity skeleton ngay khi chốt ít nhất `CONF-01` đến `CONF-09`, `CONF-23`; Social cần `CONF-10` đến `CONF-12`; Learning cần `CONF-13` đến `CONF-16`; Chat/Notification/AI chỉ bắt đầu khi các nhóm confirm tương ứng hoàn tất.

Các tài liệu nền liên quan: [Database design](../database/README.md), [quyết định database mở](../database/10-open-decisions.md), [ma trận UI → dữ liệu](../database/11-ui-to-data-coverage.md), và [project specification](../specs/PROJECT_SPEC.md).
