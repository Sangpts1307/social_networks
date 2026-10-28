# Phân quyền hai lớp và ví dụ phòng ban

Ngày cập nhật: 20/10/2026.

## Cơ chế đã triển khai

1. Role cấp hành động, ví dụ `identity.users.read` hoặc `learning.documents.publish`.
2. Mỗi lần gán role có scope `organization`, `org_unit`, hoặc `self`; phòng ban có tùy chọn `include_descendants` và assignment có `expires_at`.
3. Máy chủ xét **cặp quyền + scope trong cùng assignment**. Không ghép quyền của role A với scope của role B.
4. Tài liệu có thêm luật theo tổ chức, phòng ban, role, người dùng; `allow`/`deny`, mức `view`/`download`/`manage`, thời gian bắt đầu/kết thúc.
5. Chức danh `job_title` và `manager_user_id` không tự cấp quyền. Phòng ban sở hữu tài liệu (`documents.org_unit_id`) cũng không tự tạo một ACL chỉ cho phòng ban đó.

Tổ chức là ranh giới bắt buộc. Không có grant phù hợp thì từ chối. Danh sách và bản ghi chi tiết dùng cùng kiểm tra phạm vi. Luật tài liệu chỉ giới hạn thêm, không tự cấp permission thiếu ở role. Không tích hợp dịch vụ AWS; đây là cơ chế của ứng dụng lấy cảm hứng từ policy IAM.

## Cây tổ chức ví dụ

```text
Công ty
├── Nhân sự (HR)
├── Kỹ thuật (ENG)
│   ├── Backend (ENG-BE)
│   └── Frontend (ENG-FE)
├── QA và Tri thức (QA)
├── Kinh doanh (SALES)
└── Tài chính (FIN)
```

Các tên dưới đây là nhân sự giả định để giải thích. Seeder `ScopedRbacExampleSeeder` tạo các phòng ban và ba mẫu role; không tạo tài khoản, đổi mật khẩu hoặc gán quyền cho người thật. Phải chủ động chọn user và cấp assignment qua Admin/API.

## 1. Phòng Nhân sự

**Lan – Trưởng phòng Nhân sự:** gán `hr_manager` ở scope `organization` vì công việc quản lý nhân sự bao phủ toàn công ty. Nhờ vậy Lan được xem/cập nhật/mở khóa người dùng và quản lý cơ cấu tổ chức. Role này hiện không có `identity.roles.manage`, nên Lan không được cấp role cho người khác.

**Mai – Chuyên viên Nhân sự:** gán `member` toàn tổ chức để đọc tài liệu nội bộ công khai; gán thêm `department_manager` chỉ tại HR nếu Mai được quản lý tài khoản trong phòng HR. Role mẫu này gồm xem, cập nhật, mở khóa người dùng, đọc cơ cấu tổ chức; không gồm cấp role.

Tài liệu “Quy trình tuyển dụng”: có thể không đặt luật Allow nếu muốn toàn công ty đọc/tải. Tài liệu “Hồ sơ lương HR”: đặt Allow phòng HR mức `view`, và Allow riêng Lan mức `manage` để Lan tiếp tục quản lý ACL.

Kết quả:

| Thao tác | Mai | Lan |
|---|---|---|
| Xem/sửa tài khoản nhân viên HR | Có | Có |
| Sửa tài khoản nhân viên Kỹ thuật | Không | Có |
| Cấp role | Không | Không |
| Đọc hồ sơ lương HR | Có nếu có permission đọc | Có |
| Tải hồ sơ lương HR | Không với luật chỉ `view` | Có với luật `manage` và permission đọc |

`view` hiện cấp quyền xem metadata/chi tiết tài liệu. Chưa có trình xem nội dung file an toàn trên trình duyệt; việc tải hoặc nhận URL file vẫn cần `download`/`manage`.

## 2. Phòng Kỹ thuật và các team con

**Minh – Trưởng phòng Kỹ thuật:** gán `department_manager` tại ENG, bật “Gồm đơn vị con”. Minh được quản lý tài khoản ở ENG, Backend và Frontend, không được quản lý HR/FIN/SALES.

**Hùng – Trưởng team Backend:** gán `department_manager` tại ENG-BE, tắt đơn vị con. Hùng chỉ quản lý nhân viên Backend. Hùng không được sửa nhân viên Frontend dù hai team cùng thuộc Kỹ thuật.

**An – Kỹ sư Backend:** gán `member` toàn tổ chức và `document_editor` tại ENG-BE. Khi tạo tài liệu “Quy ước API”, An phải chọn phòng sở hữu ENG-BE. An được tạo/tải lên/sửa tài liệu trong scope Backend nhưng không có quyền duyệt/xuất bản.

Tài liệu “Runbook Kỹ thuật” sở hữu bởi ENG: để cả Backend/Frontend đọc, thêm Allow cho từng phòng ENG, ENG-BE, ENG-FE ở mức `download`. Luật ACL phòng ban khớp thành viên trực tiếp; tùy chọn kế thừa cây hiện áp dụng cho assignment role, không tự áp dụng cho ACL tài liệu.

Nếu An chỉ có `learning.documents.read` từ một assignment scope ENG-BE thì An không đọc được tài liệu sở hữu ENG, dù ACL cho phép Backend. Để đọc tài liệu chia sẻ của công ty, dùng role `member` scope organization, còn quyền chỉnh sửa vẫn giữ ở scope ENG-BE.

## 3. Phòng QA và Tri thức

**Trang – Chuyên gia tri thức:** gán `qa_expert` scope organization khi Trang phụ trách taxonomy và kiểm duyệt hỏi đáp toàn công ty. Tài liệu restricted vẫn phải có ACL phù hợp; `qa_expert` không bỏ qua Deny.

**Linh – Người duyệt tài liệu Kỹ thuật:** gán `member` organization và `document_reviewer` tại ENG, bật đơn vị con. Linh được duyệt/xuất bản/lưu trữ tài liệu có phòng sở hữu ENG/ENG-BE/ENG-FE. Với tài liệu có Allow list, cần thêm luật `manage` phù hợp cho Linh hoặc role reviewer. Linh không được duyệt tài liệu FIN.

Các thao tác taxonomy, moderation hỏi đáp và quản lý khóa học hiện yêu cầu grant scope organization vì các tài nguyên này chưa có mô hình scope phòng ban. Không gán role scope phòng ban rồi mặc định cho thao tác toàn công ty.

## 4. Phòng Kinh doanh

**Bình – Trưởng phòng Kinh doanh:** gán `department_manager` tại SALES, thêm `member` toàn tổ chức.

**Vy – Chuyên viên:** gán `member` toàn tổ chức. Chức danh “Chuyên viên kinh doanh” không cho Vy quyền sửa tài khoản.

Tài liệu “Bảng giá đối tác”: Allow SALES mức `view`; Allow riêng Bình mức `manage`; Deny riêng Vy mức `download` nếu cần mô tả ngoại lệ trên một luật Allow tải rộng hơn.

Kết quả: Vy thấy metadata bảng giá nếu có Allow xem; không nhận URL tải. Nhân viên HR không thấy tài liệu trong danh sách và gọi trực tiếp ID cũng nhận 404. Nếu Vy có một Allow download khác nhưng đồng thời có Deny download còn hạn, Deny vẫn thắng. Deny download không chặn quyền xem metadata; Deny view chặn cả xem và tải.

## 5. Phòng Tài chính

**Hà – Trưởng phòng Tài chính:** gán `department_manager` tại FIN và `document_editor` tại FIN, thêm `member` organization. Nếu Hà cần duyệt/xuất bản, cấp thêm `document_reviewer` FIN; chức danh trưởng phòng không tự cấp việc này.

Tài liệu “Kế hoạch ngân sách”: Allow FIN mức `download`, Allow riêng Hà mức `manage`. Người dùng có role member toàn công ty vẫn không được đọc tài liệu này nếu không khớp Allow list.

**Nam – Kiểm toán viên tạm thời:** gán `member` có thời hạn, hoặc dùng tài khoản đã có quyền đọc; thêm Allow riêng Nam mức `view` với `starts_at`/`ends_at` cụ thể. Nam chỉ đọc trong thời gian luật có hiệu lực, không tải. Sau khi luật hết hạn, tài liệu vẫn restricted: hệ thống không tự quay về cho toàn tổ chức đọc.

Quyền quản lý ACL cần permission `learning.documents.access.manage` trong scope phù hợp **và** ACL `manage` nếu tài liệu đã restricted. Mẫu `document_reviewer` không cấp quyền đổi ACL; việc này do `qa_expert` hoặc quản trị được cấp riêng thực hiện.

## 6. Một người kiêm nhiệm hai phòng

Dũng có hai assignment:

| Role | Phạm vi | Tác dụng |
|---|---|---|
| `department_manager` | ENG-BE | Sửa/mở khóa tài khoản Backend |
| `member` | organization | Đọc tài liệu nội bộ nếu ACL cho phép |

Dũng đọc được tài liệu công ty nhưng không được sửa tài khoản HR. Nếu thêm quyền đọc user tại SALES, Dũng vẫn không được sửa tài khoản SALES: assignment cấp quyền sửa chỉ chứa scope ENG-BE.

## 7. API gán role và tài liệu mẫu

Dùng UUID thật lấy từ API danh sách role/phòng ban/người dùng. Các chuỗi trong dấu `<...>` dưới đây là chỗ cần thay.

```json
{
  "role_id": "<department_manager_role_id>",
  "scope_type": "org_unit",
  "org_unit_id": "<ENG_id>",
  "include_descendants": true,
  "expires_at": "2026-12-31T23:59:00+07:00"
}
```

Gửi tới `POST /api/v1/admin/users/<user_id>/role-assignments` qua Gateway. `scope_type=self` không có `org_unit_id`; chỉ quản trị gốc được cấp kiểu self trong phiên bản này. Assignment cũ có phòng ban được migrate thành org_unit; assignment cũ không có phòng ban giữ organization.

Tạo tài liệu qua `POST /api/v1/admin/learning/documents`:

```json
{
  "topic_id": "<topic_id>",
  "title": "Quy ước API Backend",
  "org_unit_id": "<ENG-BE_id>"
}
```

Đặt ACL qua `PUT /api/v1/admin/learning/documents/<document_id>/access-rules`:

```json
{
  "rules": [
    {"principal_type": "department", "principal_id": "<FIN_id>", "effect": "allow", "access_level": "view"},
    {"principal_type": "user", "principal_id": "<manager_id>", "effect": "allow", "access_level": "manage"},
    {"principal_type": "user", "principal_id": "<auditor_id>", "effect": "allow", "access_level": "view", "starts_at": "2026-10-20T08:00:00+07:00", "ends_at": "2026-10-31T18:00:00+07:00"}
  ]
}
```

Luật `manage` bao hàm đọc/tải nhưng không tự sinh các permission sửa/duyệt/xuất bản. Không có luật Allow: giữ quyền mặc định theo tổ chức và scope của role. Có ít nhất một Allow: chỉ chủ thể khớp Allow đang hiệu lực mới vào; Allow hết hạn vẫn giữ tài liệu restricted. Luật Deny đang hiệu lực được ưu tiên; quản trị hệ thống cũng không bypass ACL tài liệu.

## 8. Giới hạn cấp quyền và vận hành

- Không tự gán/gỡ role hoặc sửa permission của role đang gán cho mình.
- Người cấp thường phải có `identity.roles.manage` và từng permission định cấp, với scope/thời hạn bao phủ assignment mới. Không thể cấp role super_admin. Quản trị gốc scope organization có đặc quyền cấp role.
- Sửa permission của role là thay đổi catalog chung, yêu cầu quyền organization và không cho thêm/gỡ permission ngoài thẩm quyền người cấp.
- Sửa cấu trúc phòng ban và đọc audit log yêu cầu scope organization. API người dùng, dashboard và danh sách phòng ban áp dụng scope phù hợp.
- Scope theo self/phòng ban chưa được ánh xạ vào thao tác quản trị Social. Gateway chỉ chuyển quyền quản trị organization cho Social; kiểm tra membership kênh/chủ bài vẫn giữ nguyên.
- Context phân quyền hiện truyền qua Gateway tin cậy; số lượng rất lớn assignment/phòng ban cần chuyển sang token tham chiếu hoặc introspection thay vì tăng header không giới hạn.
- URL tải đã ký có hiệu lực đến thời điểm hết hạn riêng. Thu hồi ACL chặn lần cấp URL tiếp theo, không hủy ngay URL đã cấp.
- Tài liệu cũ chưa có phòng sở hữu giữ `org_unit_id = null`; quyền organization vẫn dùng được, grant phòng ban không tự bao phủ chúng.

Giao diện: **Người dùng** có scope, phòng ban, gồm team con, hạn cấp quyền và nút **Quyền hiệu lực**. **Tài liệu tri thức** có phòng sở hữu và trình sửa ACL. Chọn giờ cấp quyền theo giờ Việt Nam; API/DB dùng thời gian UTC.

## Kiểm chứng

Đã áp dụng hai migration bổ sung và seed danh mục ví dụ ở local. Không thay đổi assignment/tài khoản hiện có khi seed ví dụ.

- Bộ kiểm thử Identity: 19 ca; Learning: 9 ca; Admin: 3 ca.
- Gateway build/typecheck và 7 hợp đồng OpenAPI được kiểm tra.
- `python3 scripts/test-scoped-authorization-local.py` chạy Identity + Gateway + Learning thật với MySQL tách biệt; kiểm tra scope người dùng, tạo tài liệu đúng phòng, ACL chỉ xem và Deny. Kết thúc tự dọn database/account thử nghiệm.
- Template Admin được biên dịch; chưa kiểm tra bố cục trực quan bằng trình duyệt.

Lưu ý giao diện ACL: danh sách người dùng hiện lấy tối đa 100 người trong scope đọc được; việc quản lý danh mục lớn hơn nên dùng API hoặc mở rộng bộ tìm kiếm/phân trang. Nếu không có quyền đọc danh mục role/người dùng, danh sách lựa chọn tương ứng có thể trống. Quyền truy cập thực tế luôn do backend kiểm tra.
