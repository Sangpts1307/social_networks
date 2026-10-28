# Tiến độ triển khai

## Đối soát mới nhất — 22/10/2026

[Xem tiến độ toàn đồ án ngày 22/10](SYSTEM_PROGRESS_2026-10-22.md). Đã bổ sung 77 operation REST, Gateway kiểm kê 150 operation; đợt API 21/10 ghi nhận 63 test đạt. Chat/Notification đã có nghiệp vụ; Mobile đã có source React Native/Expo và demo Social, không còn chỉ README. AI có base provider, graph vẫn demo. Chưa nghiệm thu xuyên hệ thống: còn cấu hình DB Social/Notification, hạ tầng thật, nối UI và native mobile. Course/Lesson tiếp tục hoãn; thưởng Tri thức tích lũy chờ Admin duyệt tuần/tháng.

Các phần có ngày cũ bên dưới là lịch sử, không dùng làm hiện trạng 22/10. [Ảnh chụp 14/10](SYSTEM_PROGRESS_2026-10-14.md) được giữ để đối chiếu. Không tự đổi trạng thái task chi tiết sang DONE trong lần tổng hợp này.

> Cập nhật cấu trúc ngày 2026-09-15. Đã có source nền tảng; tiến độ từng task cần đối soát.

## 0. Đối soát tiến độ ĐATN — 2026-10-13

Bản đối soát lịch sử và lộ trình theo bảy hạng mục giao nhiệm vụ nằm tại [Tiến độ ĐATN ngày 13/10/2026](THESIS_PROGRESS_2026-10-13.md). Thời gian chính thức: 05/10/2026–18/01/2027; mục tiêu nội bộ đề xuất: 03/01/2027.

- Admin Identity: tracker ADM-001–009 đã ghi DONE ngày 27/09; chưa chạy lại nghiệm thu trong lần đối soát này.
- Learning: source đã có upload/version, review, publish/archive/restore, access rules và outbox, vượt trạng thái metadata-only của tổng hợp cũ. Chạy lại `php artisan test` ngày 13/10 đạt 5 tests/79 assertions; dùng SQLite in-memory và storage giả lập, chưa chứng minh storage/quét file thật hay browser E2E.
- Q&A backend đã có; một số UI còn mock. Social routes mới có health, Android chưa có project, graph API vẫn trả fixture; chưa có bằng chứng AI production hoạt động.
- Giữ trạng thái task chi tiết cho đến khi có đủ bằng chứng từng task. Không dùng số task trong ảnh chụp cũ làm tỷ lệ hoàn thành đồ án hiện tại.

### Bổ sung Social backend ngày 13/10/2026

Chủ dự án yêu cầu mở Social và API dùng chung Web/Mobile. Đã có API protected qua Gateway cho channels/self-membership, text posts/feed/cursor, comment/reply, reaction, private save, report và post moderation; có OpenAPI công khai/nội bộ, retry idempotency, rate limit, outbox và audit. Kiểm thử: 13 Feature tests/137 assertions SQLite; cùng suite MySQL, HTTP Identity → Gateway → Social và concurrent retry PASS, cleanup DB/account thử PASS. [Chi tiết tích hợp](../specs/phase-1-social/02-api-client-guide.md).

Phần Social mock được mô tả ở đối soát đầu ngày bên trên nay đã có backend core thật; Vue/Android vẫn chưa nối. Attachment/event/follow/managed membership/moderation comment và event relay chưa hoàn tất; chưa đóng P1. Báo cáo THESIS_PROGRESS_2026-10-13.md là ảnh chụp trước đợt triển khai Social này.

## Ảnh chụp lịch sử — 2026-09-29

Đây là bản tổng hợp để kiểm tra nhanh từ các tracker phase và bằng chứng đã ghi; **không phải** kết quả chạy lại toàn bộ hệ thống ngày hôm nay. Trạng thái chi tiết và bằng chứng của từng task vẫn được quản lý tại file phase tương ứng.

| Hạng mục | Trạng thái hiện tại | Bằng chứng / việc còn lại |
|---|---|---|
| P0 — Foundation, Identity, Knowledge Graph demo | `IN_PROGRESS` | Foundation source, Identity auth/session, Gateway proxy và Web auth đã có bằng chứng PASS. Chưa đủ điều kiện đóng P0: browser E2E thật, RBAC/Admin/Audit, dependency local còn thiếu và Neo4j thật. |
| P2 — Learning / Knowledge Foundation | `IN_PROGRESS` | Taxonomy + metadata published đã qua Gateway; Q&A schema/MVP đang làm. Cần hoàn tất R0 browser E2E, sau đó Q&A UI/comments và event/provenance. |
| P1 Social; P3 Chat/Notification | `DEFERRED` | Chưa mở implementation; chờ P0/P2 và contract phụ thuộc ổn định. |
| P4 AI production; P5 Mobile; P6 Release | `BLOCKED` / `DEFERRED` | Chờ các quyết định về AI/hạ tầng, stack mobile và phạm vi phát hành. |

### Tổng số work item trong tracker phase

| `DONE` | `IN_PROGRESS` | `BLOCKED` | `DEFERRED` | `NEEDS_REVIEW` |
|---:|---:|---:|---:|---:|
| 40 | 14 | 12 | 78 | 53 |

`NEEDS_REVIEW` là backlog đã tách nhưng chưa được kiểm toán source; không được hiểu là đã triển khai. Các số trên được tổng hợp từ `docs/specs/phase-*` ngày 2026-09-29.

### Ưu tiên công việc kế tiếp

1. Hoàn tất browser E2E bằng account test cô lập cho login/đổi mật khẩu và Base Square (điều kiện còn lại của `WEB-ID-001/002`, `LEARN-WEB-001/002`, mốc R0).
2. Hoàn thiện Q&A MVP: OpenAPI + Feature test + Gateway integration + UI/comments; giữ quy tắc best-answer derived-only.
3. Mở event/provenance và graph deterministic chỉ sau Q&A/source lifecycle; không thay fixture graph bằng dữ liệu thật trước khi có Neo4j runtime.
4. Song song, đóng các dependency P0 có thể kiểm chứng: local services, RBAC/Admin/Audit, OpenAPI toàn cục và hướng dẫn clean checkout.

### File quản lý để kiểm tra/cập nhật

- Tổng hợp và decision/idea log: chính file này.
- Roadmap: [`../specs/ROADMAP.md`](../specs/ROADMAP.md).
- Trạng thái task, owner, dependency và bằng chứng: từng thư mục [`../specs/phase-*`](../specs/README.md), ưu tiên P0 và P2 đang mở.
- Kế hoạch triển khai Learning → Graph → AI: [`../specs/phase-2-learning/03-execution-plan.md`](../specs/phase-2-learning/03-execution-plan.md).

Lần cập nhật tracker tiếp theo phải ghi bằng chứng chạy mới (test/build/integration/E2E) trước khi chuyển một task sang `DONE`.

## Nơi cập nhật tiến độ

Toàn bộ công việc đã tách vào [specs theo phase](../specs/README.md). Chỉ cập nhật trạng thái task ở các file phase để tránh trùng dữ liệu.

Bắt đầu tại [Phase 0](../specs/phase-0-foundation-identity/README.md). Phạm vi theo [baseline](IMPLEMENTATION_BASELINE.md).

Bản tracker trước khi tách được giữ trong [lịch sử](../archive/IMPLEMENTATION_PROGRESS-2026-09-12.md).

## 10. Idea Inbox — chưa được phép code

Thêm ý tưởng mới ở đây trước. Một idea chỉ được chuyển thành work item sau khi chủ dự án ghi `APPROVED`; khi đó gán ID service và dependency.

| Idea ID | Ý tưởng | Service dự kiến | Trạng thái | Quyết định/ghi chú |
|---|---|---|---|---|
| IDEA-001 | Mobile app: React Native + Expo + TypeScript | Mobile | `IN_PROGRESS` | Đã có source/demo Expo và bundle kiểm tra 19–21/10; chưa nghiệm thu API thật/native |
| IDEA-002 | AI provider, embedding và hybrid search thật | AI Knowledge | `IN_PROGRESS` | Có base OpenAI cấu hình qua env; chưa gọi thật; embedding/hybrid/KG pipeline chưa triển khai |
| IDEA-003 | Coin quy đổi tiền thưởng và bounty settlement | Learning | `BLOCKED` | Cần finance, anti-fraud, approval/reversal policy |
| IDEA-004 | Email notification | Notification | `DEFERRED` | Baseline hiện tại chỉ in-app + push |
| IDEA-005 | Course/Lesson authoring, quiz, certificate | Learning | `DEFERRED` | Cần product flow riêng |
| IDEA-006 | Fine-grained document ACL theo org-unit/role/user | Learning | `IN_PROGRESS` | Có scoped ACL trong source; xem tài liệu security và kiểm tra quyền xuyên luồng trước nghiệm thu |
| IDEA-007 | Chat call/video, edit/delete message | Chat | `DEFERRED` | Ngoài Chat MVP |

---

## 11. Decision & Change Log

Chỉ ghi thay đổi đã được chủ dự án chấp thuận. Không ghi nhật ký tool/terminal nhỏ ở đây.

| Date | ID | Quyết định hoặc thay đổi | Ảnh hưởng | Approved by |
|---|---|---|---|---|
| 2026-09-12 | BASELINE-001 | Chốt Identity credential trong `users`; không OTP/activation; login email, lock sau 10 lần đến Admin unlock | Identity schema/API/test | Chủ dự án |
| 2026-09-12 | BASELINE-002 | Chốt Social không có Poll/Vote; có event RSVP, comment/reply/reaction/save/follow/moderation | Social schema/API | Chủ dự án |
| 2026-09-12 | BASELINE-003 | Chốt Phase 0 là Identity/RBAC/Admin + demo Cây tri thức; Web Vue/Laravel | P0 sequencing | Chủ dự án |
| 2026-09-12 | BASELINE-004 | Coin quy đổi thưởng và AI provider production chưa triển khai | Learning/AI scope | Chủ dự án |

---


## Quyết định cách chạy — 2026-09-16

Chủ dự án yêu cầu local trực tiếp trên môi trường máy đã cài trước, Docker build/run sau. Đã cập nhật baseline và PLAT-004/005/006/015; thêm PLAT-016–018 cho Docker. Không thay đổi trạng thái thành DONE khi chưa có kiểm chứng runtime.

## Nhật ký bổ sung — 2026-10-22

- Đồng bộ báo cáo tiến độ với coverage API 21/10 và ghi nhận Mobile 19–21/10; không chạy lại toàn bộ test trong lần cập nhật tài liệu.
- Quyết định chủ dự án: hoãn Course/Lesson; Social tách Tri thức; Admin chỉnh points/coin; thưởng tổng kết tuần/tháng, lưu lịch sử đã duyệt; hủy pending khi bỏ vote hoặc xóa/ẩn nội dung.
- Base AI cấu hình key/model qua môi trường đã có; payout/bounty và AI/KG nâng cao chưa hoàn tất.
- Giữ các ảnh chụp và số task cũ làm lịch sử, không dùng để tính phần trăm đồ án.
