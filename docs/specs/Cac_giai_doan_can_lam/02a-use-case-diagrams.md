# Bộ Biểu đồ Use Case Tổng quát & Phân rã (Dùng cho Báo cáo ĐATN)

Tài liệu này cung cấp đầy đủ các **Biểu đồ Use Case tổng quát và phân rã** (kèm quan hệ `<<include>>`, `<<extend>>` và phân nhóm Actor) theo chuẩn báo cáo Đồ án Tốt nghiệp, đồng thời cung cấp **Bảng tra cứu toàn bộ đường dẫn nguồn trong dự án** cho các mục 1, 2, 3, 4.

---

## 1. Biểu đồ Use Case Tổng quát Toàn hệ thống (System-Level Use Case)

```mermaid
flowchart LR
  subgraph Actors["Tác nhân (Actors)"]
    EMP(["Nhân viên (Employee)"])
    MOD(["Kiểm duyệt viên / Quản lý kênh (Moderator)"])
    CUR(["Chuyên gia tri thức (Curator / Reviewer)"])
    ADM(["Quản trị viên hệ thống (System Admin)"])
  end

  subgraph System["Hệ thống Mạng xã hội Doanh nghiệp & Quản trị Tri thức (Openhello.vn / NexaHub)"]
    UC_AUTH(["UC01. Đăng nhập, Đổi mật khẩu & Quản lý Hồ sơ"])
    UC_SOC(["UC02. Giao tiếp Mạng xã hội nội bộ (Bài viết, Kênh, Tương tác)"])
    UC_CHAT(["UC03. Nhắn tin trực tiếp, Nhóm & Nhận thông báo"])
    UC_DOC(["UC04. Quản lý, Tra cứu & Tải tài liệu nội bộ"])
    UC_QA(["UC05. Hỏi đáp chuyên môn, Bình chọn & Tích điểm thưởng"])
    UC_MOD(["UC06. Kiểm duyệt bài viết, Báo cáo & Phê duyệt tài liệu"])
    UC_KG(["UC07. Tìm kiếm tri thức, Duyệt khái niệm AI & Knowledge Graph"])
    UC_ADM(["UC08. Quản trị tài khoản, Phòng ban, Phân quyền RBAC & Kỳ thưởng"])
  end

  EMP --> UC_AUTH
  EMP --> UC_SOC
  EMP --> UC_CHAT
  EMP --> UC_DOC
  EMP --> UC_QA
  EMP --> UC_KG

  MOD --> UC_AUTH
  MOD --> UC_SOC
  MOD --> UC_MOD

  CUR --> UC_AUTH
  CUR --> UC_DOC
  CUR --> UC_MOD
  CUR --> UC_KG

  ADM --> UC_AUTH
  ADM --> UC_MOD
  ADM --> UC_ADM
```

---

## 2. Các Biểu đồ Use Case Phân rã theo 5 Phân hệ Nghiệp vụ

### 2.1. Phân rã 1: Mạng xã hội nội bộ & Hồ sơ (Identity & Base Inside)

```mermaid
flowchart LR
  EMP(["Nhân viên (Employee)"])
  MOD(["Người có quyền đăng tin / Quản lý kênh"])

  subgraph SocialSub["Phân hệ Mạng xã hội nội bộ (Base Inside - Web & Mobile)"]
    S1(["Đăng nhập & Khôi phục phiên"])
    S1_1(["Đổi mật khẩu lần đầu bắt buộc"])
    S2(["Xem & Cập nhật Hồ sơ cá nhân"])
    S3(["Xem Bảng tin, Lọc theo kênh & Tìm kiếm bài viết"])
    S4(["Đăng, Sửa, Xóa bài viết thường / Sự kiện"])
    S5(["Đăng Thông báo chính thức / Bản tin nội bộ"])
    S6(["Tương tác: Thả cảm xúc, Bình luận, Trả lời, Lưu bài"])
    S7(["Xác nhận đã đọc Thông báo / RSVP Sự kiện"])
    S8(["Xem danh sách kênh, Tham gia / Rời kênh mở"])
    S9(["Báo cáo bài viết vi phạm"])
    AUTH_CHECK(["Kiểm tra phiên & Quyền truy cập kênh"])
  end

  EMP --> S1
  EMP --> S2
  EMP --> S3
  EMP --> S4
  EMP --> S6
  EMP --> S7
  EMP --> S8
  EMP --> S9

  MOD --> S5

  S1 -. "<<extend>> (nếu must_change_password)" .-> S1_1
  S3 -. "<<include>>" .-> AUTH_CHECK
  S4 -. "<<include>>" .-> AUTH_CHECK
  S5 -. "<<include>>" .-> AUTH_CHECK
  S6 -. "<<include>>" .-> AUTH_CHECK
```

---

### 2.2. Phân rã 2: Quản trị Kho tài liệu (Base Library)

```mermaid
flowchart LR
  EMP(["Nhân viên (Employee)"])
  CUR(["Người đóng góp / Quản trị tài liệu"])
  SCAN(["Dịch vụ Quét an toàn tệp (Scanner)"])

  subgraph DocSub["Phân hệ Quản trị Kho tài liệu (Base Library)"]
    D1(["Duyệt thư viện theo Chủ đề & Chuyên mục"])
    D2(["Xem chi tiết tài liệu & Lịch sử phiên bản"])
    D3(["Tải xuống tài liệu qua URL ký có thời hạn"])
    D4(["Tạo tài liệu mới & Tải lên phiên bản tệp"])
    D5(["Cập nhật trạng thái quét mã độc (clean / infected)"])
    D6(["Thiết lập quy tắc phân quyền đọc (Access Rules)"])
    D7(["Lưu trữ (Archive) / Khôi phục (Restore) tài liệu"])
    ACL_CHECK(["Kiểm tra ACL Tổ chức / Đơn vị & Trạng thái Clean"])
  end

  EMP --> D1
  EMP --> D2
  EMP --> D3

  CUR --> D4
  CUR --> D6
  CUR --> D7

  SCAN --> D5

  D2 -. "<<include>>" .-> ACL_CHECK
  D3 -. "<<include>>" .-> ACL_CHECK
  D4 -. "<<include>>" .-> D5
```

---

### 2.3. Phân rã 3: Kiểm duyệt nội dung (Moderation & Governance)

```mermaid
flowchart LR
  EMP(["Nhân viên (Employee)"])
  MOD(["Kiểm duyệt viên MXH (social.moderation.manage)"])
  CUR(["Người duyệt tài liệu (learning.documents.approve)"])

  subgraph ModSub["Phân hệ Kiểm duyệt nội dung & Tài liệu"]
    M1(["Gửi báo cáo bài viết vi phạm (spam, nhạy cảm...)"])
    M2(["Xem danh sách báo cáo chờ xử lý / đã xử lý"])
    M3(["Ẩn bài viết vi phạm (Hide Post)"])
    M4(["Khóa bình luận bài viết (Lock Comments)"])
    M5(["Khôi phục bài viết đã ẩn (Restore Post)"])
    M6(["Phê duyệt (Approve) hoặc Từ chối (Reject) phiên bản tài liệu"])
    M7(["Công bố tài liệu ra thư viện (Publish Document)"])
    AUDIT(["Ghi nhật ký kiểm duyệt (Audit Log & Outbox Event)"])
  end

  EMP --> M1
  MOD --> M2
  MOD --> M3
  MOD --> M4
  MOD --> M5

  CUR --> M6
  CUR --> M7

  M3 -. "<<include>>" .-> AUDIT
  M4 -. "<<include>>" .-> AUDIT
  M5 -. "<<include>>" .-> AUDIT
  M6 -. "<<include>>" .-> AUDIT
  M7 -. "<<include>>" .-> AUDIT
```

---

### 2.4. Phân rã 4: Hỏi đáp chuyên môn & Cơ chế thưởng (Base Square Q&A & Rewards)

```mermaid
flowchart LR
  EMP(["Nhân viên (Employee)"])
  ADM(["Quản trị viên Thưởng (Admin)"])

  subgraph QASub["Phân hệ Hỏi đáp chuyên môn & Tích điểm thưởng"]
    Q1(["Tìm kiếm & Xem danh sách câu hỏi theo chủ đề"])
    Q2(["Đặt câu hỏi mới / Sửa / Xóa mềm câu hỏi"])
    Q3(["Gửi câu trả lời & Bình luận trao đổi"])
    Q4(["Bình chọn câu hỏi / câu trả lời / tài liệu (Upvote / Downvote)"])
    Q5(["Chọn câu trả lời đúng nhất (Accept Best Answer)"])
    Q6(["Tự động ghi nhận khoản thưởng chờ duyệt (Pending Award)"])
    Q7(["Xem Ví điểm, Lịch sử tích lũy & Bảng xếp hạng (Leaderboard)"])
    Q8(["Cấu hình quy tắc tính điểm & Giới hạn theo ngày/chủ đề"])
    Q9(["Lập kỳ tổng kết Tuần/Tháng & Phê duyệt cộng điểm vào Ví"])
  end

  EMP --> Q1
  EMP --> Q2
  EMP --> Q3
  EMP --> Q4
  EMP --> Q5
  EMP --> Q7

  ADM --> Q8
  ADM --> Q9

  Q4 -. "<<extend>> (khi đủ điều kiện rule)" .-> Q6
  Q5 -. "<<extend>>" .-> Q6
```

---

### 2.5. Phân rã 5: Tìm kiếm tri thức, AI Curation & Knowledge Graph (AI & Neo4j)

```mermaid
flowchart LR
  EMP(["Nhân viên (Employee)"])
  CUR(["Chuyên gia duyệt tri thức (ai.curation.review)"])
  NEO(["Cơ sở dữ liệu Đồ thị Neo4j"])

  subgraph KGSub["Phân hệ Tìm kiếm Tri thức & Knowledge Graph"]
    K1(["Tìm kiếm tri thức đa nguồn (Bài viết, Câu hỏi, Tài liệu)"])
    K2(["Xem tóm tắt AI & Khái niệm gợi ý từ câu hỏi/tài liệu"])
    K3(["Khám phá Đồ thị tri thức (Graph Explorer) & Chuyên gia"])
    K4(["Đề xuất khái niệm kèm nguồn dẫn chứng (Concept Proposal)"])
    K5(["Xem hàng đợi khái niệm chờ duyệt (Pending Curation)"])
    K6(["Phê duyệt / Chỉnh sửa / Từ chối khái niệm"])
    K7(["Đồng bộ idempotent (MERGE) Node & Quan hệ sang Neo4j"])
    SRC_VERIFY(["Đối chiếu quyền truy cập & Hash nguồn Learning"])
  end

  EMP --> K1
  EMP --> K2
  EMP --> K3
  EMP --> K4

  CUR --> K5
  CUR --> K6
  CUR --> K7

  K1 -. "<<include>>" .-> SRC_VERIFY
  K4 -. "<<include>>" .-> SRC_VERIFY
  K6 -. "<<include>>" .-> SRC_VERIFY
  K7 --> NEO
```

---

## 3. Mã PlantUML chuẩn (Để dán vào PlantUML / Draw.io xuất ảnh chèn báo cáo Word)

Nếu bạn dùng **PlantUML** hoặc **Draw.io** (`Arrange -> Insert -> Advanced -> PlantUML` hoặc `Mermaid`) để xuất ảnh độ phân giải cao cho báo cáo Word, có thể dùng trực tiếp khối PlantUML dưới đây:

```plantuml
@startuml
left to right direction
skinparam packageStyle rectangle
skinparam shadowing false

actor "Nhân viên\n(Employee)" as EMP
actor "Kiểm duyệt viên\n(Moderator)" as MOD
actor "Chuyên gia tri thức\n(Curator / Reviewer)" as CUR
actor "Quản trị viên\n(System Admin)" as ADM

EMP <|-- MOD
EMP <|-- CUR
MOD <|-- ADM

rectangle "Hệ thống Mạng xã hội Doanh nghiệp & Quản trị Tri thức (Openhello.vn)" {
  package "1. Định danh & Mạng xã hội (Base Inside)" {
    usecase "UC01. Đăng nhập, Đổi mật khẩu & Hồ sơ" as UC1
    usecase "UC02. Xem Bảng tin, Tìm kiếm & Lọc kênh" as UC2
    usecase "UC03. Đăng, Sửa, Xóa bài viết & Sự kiện" as UC3
    usecase "UC04. Tương tác (Cảm xúc, Bình luận, Lưu bài)" as UC4
    usecase "UC05. Tham gia / Rời kênh & Xem thành viên" as UC5
    usecase "UC06. Đăng Thông báo chính thức / Bản tin" as UC6
  }

  package "2. Kho tài liệu & Hỏi đáp (Base Square)" {
    usecase "UC07. Tra cứu, Đọc & Tải xuống tài liệu" as UC7
    usecase "UC08. Tải lên tài liệu & Quản lý phiên bản" as UC8
    usecase "UC09. Đặt câu hỏi, Trả lời & Bình chọn (Vote)" as UC9
    usecase "UC10. Chọn Best Answer & Xem Ví thưởng" as UC10
  }

  package "3. Kiểm duyệt & Quản trị (Moderation & Admin)" {
    usecase "UC11. Báo cáo bài viết vi phạm" as UC11
    usecase "UC12. Ẩn/Khôi phục bài viết & Khóa bình luận" as UC12
    usecase "UC13. Phê duyệt & Công bố tài liệu" as UC13
    usecase "UC14. Quản trị Tài khoản, Phòng ban, RBAC & Kỳ thưởng" as UC14
  }

  package "4. Tìm kiếm & Đồ thị tri thức (AI & Knowledge Graph)" {
    usecase "UC15. Tìm kiếm tri thức & Khám phá đồ thị" as UC15
    usecase "UC16. Đề xuất khái niệm gắn nguồn tri thức" as UC16
    usecase "UC17. Duyệt khái niệm (Curation) & Đồng bộ Neo4j" as UC17
  }
}

EMP --> UC1
EMP --> UC2
EMP --> UC3
EMP --> UC4
EMP --> UC5
EMP --> UC7
EMP --> UC9
EMP --> UC10
EMP --> UC11
EMP --> UC15
EMP --> UC16

MOD --> UC6
MOD --> UC12

CUR --> UC8
CUR --> UC13
CUR --> UC17

ADM --> UC14
@enduml
```
