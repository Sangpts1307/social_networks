# Tiến độ ĐATN và kế hoạch hoàn thành sớm ngày 13 tháng 10 năm 2026

Đối chiếu phiếu `output/docx/Giao_nhiem_vu_tot_nghiep_da_dien.docx`, mô tả đề tài, tài liệu kiến trúc, tracker phase và mã nguồn hiện có. Thời gian chính thức ghi trong phiếu là 05/10/2026–18/01/2027. Mục tiêu nội bộ đề xuất: hoàn thiện sản phẩm và báo cáo trước 03/01/2027, dành 04–17/01 cho dự phòng và bảo vệ. Đây là kế hoạch đề xuất, không phải thay đổi lịch chính thức.

## Cách hiểu bằng chứng

- Đã kiểm tra source ngày 13/10; chỉ chạy lại bộ kiểm thử Learning, không nghiệm thu toàn hệ thống.
- Learning: `php artisan test` đạt 5 tests, 79 assertions trên SQLite in-memory. Test quản trị dùng storage giả lập và bật tin cậy upload local; không chứng minh MinIO hay quét mã độc thật đã hoạt động.
- Admin: ADM-001–009 được tracker ngày 27/09 ghi DONE; source hiện có. Chưa chạy lại kiểm thử Admin và browser E2E trong lần đối soát này.
- Không quy đổi số task DONE thành phần trăm đồ án: task khác trọng số, nhiều task mở rộng không nằm trong bảng giao nhiệm vụ, tracker còn chậm hơn source.

## Đối chiếu bảy hạng mục báo cáo

Ngày dự kiến được suy ra bằng cách cộng tuần/ngày lịch liên tiếp từ 05/10; ngày kết thúc trong bảng là ngày cuối của từng khoảng. Tổng 15 tuần kết thúc hết 17/01, phù hợp mốc nộp 18/01 ghi trong phiếu.

| STT | Hạng mục | Khoảng dự kiến | Tình hình xác minh ngày 13/10 | Phần còn lại để được coi là hoàn thành |
|---|---|---|---|---|
| 1 | Nghiên cứu, khảo sát, phạm vi và công nghệ | 05–11/10 | Có mô tả đề tài, baseline, kiến trúc và ADR; có cơ sở viết báo cáo | Chuẩn hóa tài liệu tham khảo, bảng khảo sát hệ thống, mục tiêu đo được và tiêu chí đánh giá; chưa thấy bản báo cáo hoàn chỉnh để xác nhận đã xong |
| 2 | Phân tích yêu cầu | 12–25/10 | Có functional spec, user flow, module catalog, gap analysis và traceability | Gom thành chương phân tích, Use Case và quy trình tài liệu/Q&A/kiểm duyệt; rà lại sự thống nhất với phạm vi giao nhiệm vụ |
| 3 | Thiết kế kiến trúc, API, CSDL, KG và UI | 26/10–08/11 | Đã có thiết kế service, database theo service, ERD/index plan, OpenAPI và giao diện mẫu | Chốt thiết kế mobile, ontology và pipeline AI; cập nhật sơ đồ theo triển khai hiện tại và đưa vào báo cáo |
| 4 | Mạng xã hội web/mobile | 09–29/11 | Auth/session và web login đã có; Admin Identity được ghi hoàn tất. Social mới có model/schema, routes chỉ health, BaseInside dùng mock. Mobile chỉ README | Làm API/UI thật cho bài viết, feed, tương tác, nhóm, hồ sơ và kiểm duyệt; xây ứng dụng Android và kiểm thử thiết bị |
| 5 | Kho tài liệu và kiểm duyệt | 30/11–13/12 | Vượt trạng thái tổng hợp cũ: đã có upload/version, approve/reject, publish/archive/restore, access rules, đọc/tải tài liệu và outbox; test Learning PASS | Kiểm tra qua Gateway và Admin UI/browser thật, file storage thật, quyền truy cập/download, bản bị từ chối và phiên bản mới; đồng bộ tracker chi tiết sau nghiệm thu |
| 6 | AI, Knowledge Graph, hỏi đáp và tìm kiếm/gợi ý | 14–30/12 | Q&A backend có tạo/list/detail, trả lời, vote và best-answer. KG API hiện trả fixture; chưa thấy pipeline Neo4j/vector/LLM thật. Một màn Q&A còn dùng mock | Nối UI vào API; triển khai relay/consumer idempotent, provenance/version, Neo4j thật, trích xuất có duyệt, hỏi đáp có dẫn nguồn, tìm kiếm và gợi ý; đo chất lượng |
| 7 | Kiểm thử, triển khai mẫu và hồ sơ bàn giao | 31/12–17/01 | Có kiểm thử thành phần, integration scripts và tài liệu local; chưa có bằng chứng nghiệm thu web/mobile đầy đủ | E2E, dữ liệu demo, cài từ môi trường sạch, báo cáo kết quả, hướng dẫn, gói source, bản demo và slide |

Kết luận: đã làm trước đáng kể phần phân tích/thiết kế và nền tảng tài khoản–tài liệu–Q&A. Khoảng trống lớn nhất so với đầu ra chính thức là Social thật, Android, KG/AI thật và hồ sơ đánh giá. Chưa có cơ sở kết luận toàn đồ án chậm hoặc đạt một tỷ lệ hoàn thành cụ thể.

## Lộ trình nội bộ đề xuất

Kế hoạch giả định người thực hiện làm đều hằng tuần, giữ phạm vi MVP và chốt các quyết định hạ tầng/AI trong tháng 10. Viết báo cáo và lưu ảnh/bằng chứng ngay trong từng mốc, không để đến cuối.

| Hạn nội bộ | Công việc ưu tiên | Điều kiện đóng mốc | So với hạn dự kiến |
|---|---|---|---|
| 18/10 | Hoàn chỉnh khảo sát, mục tiêu và chương mở đầu; gom tài liệu đã có | Có bảng khảo sát, nguồn tham khảo, phạm vi, tiêu chí đánh giá và bản thảo chương 1 | Hạng mục 1 đã qua mốc dự kiến; ưu tiên đóng hồ sơ ngay |
| 22/10 | Chốt yêu cầu, Use Case, workflow và ma trận chức năng–bằng chứng | Mọi yêu cầu trong phiếu có tiêu chí nghiệm thu; ghi rõ phần mở rộng | Sớm hơn hạng mục 2 ba ngày |
| 01/11 | Hoàn chỉnh kiến trúc/ERD/API/mobile/KG; nghiệm thu nền tảng tài liệu và Q&A | Browser thật: login → đổi mật khẩu → hỏi/đáp; upload → duyệt → công bố → đọc/tải; kiểm tra quyền và tổ chức | Thiết kế sớm hơn một tuần; kho tài liệu có thể đóng sớm nếu đạt đủ kiểm chứng |
| 15/11 | Social Web MVP và kiểm duyệt | Đăng bài → feed → comment/reaction → nhóm → hồ sơ; refresh vẫn giữ dữ liệu; không dùng mock cho luồng nghiệm thu | Chuẩn bị sớm cho hạng mục 4 |
| 22/11 | Android MVP dùng API đã ổn định | Cài trên thiết bị/emulator; login, feed, bài viết, tương tác, nhóm, hồ sơ theo cùng quyền Web | Sớm hơn hạng mục 4 một tuần |
| 29/11 | Chốt kho tài liệu; event → Neo4j thật | Storage và download có quyền; publish/update/archive phản ánh lên graph, có nguồn/version và replay không nhân đôi | Kho tài liệu sớm hơn hai tuần |
| 20/12 | Hoàn thiện AI và ứng dụng tri thức | Trích xuất → reviewer duyệt → KG; hỏi đáp có nguồn, tìm kiếm và gợi ý có dữ liệu thật; lưu bộ câu hỏi đánh giá và kết quả | Sớm hơn hạng mục 6 mười ngày |
| 27/12 | Đóng chức năng, demo và báo cáo bản đầy đủ | E2E các luồng chính, bản Android, bộ dữ liệu demo, chương kết quả và hạn chế, hướng dẫn chạy | Đầu vào cho nghiệm thu cuối |
| 03/01/2027 | Chốt bản nộp nội bộ | Báo cáo đã rà, source đóng gói, hướng dẫn cài, demo, slide và kịch bản bảo vệ | Trước mốc nộp 18/01 mười lăm ngày |

Ngày 04–17/01 dành cho sửa theo GVHD, lỗi nghiệm thu, diễn tập và sao lưu. Không mở chức năng mới trong khoảng này.

## Các điều kiện cần giải quyết sớm

1. Trước 18/10: xác nhận Android Kotlin/Java theo mô tả và README mobile; tracker cũ còn nói chưa chọn stack nên cần đồng bộ sau quyết định chính thức. Chốt bộ màn hình MVP.
2. Trước 25/10: chọn storage và Neo4j local cho demo, chốt cách chạy lại từ môi trường sạch. Chốt provider/model hoặc phương án local, ngân sách và dữ liệu được phép xử lý trước khi nối AI thật.
3. Không giữ thứ tự cũ “R0–R5 xong mới quay lại Social” nếu muốn đạt bảng giao nhiệm vụ: khi auth/Gateway ổn định, ưu tiên Social Web và Android; tiếp tục phần graph theo nguồn tài liệu đã ổn định. Đây là đề xuất điều chỉnh thứ tự, chưa thay trạng thái task.
4. Giữ AI và mobile trong phạm vi nghiệm thu ĐATN. Ghi chú P6 cũ cho phép phát hành không có AI/mobile chỉ phù hợp bản phát hành kỹ thuật riêng, không đáp ứng phiếu giao nhiệm vụ này.
5. Coin/quy đổi thưởng, LMS đầy đủ, Expert Finder nâng cao và chat/video nâng cao chỉ làm sau đầu ra bắt buộc. Chat cơ bản có trong mô tả ban đầu nhưng không tách trong bảng bảy mục: đối chiếu với GVHD trước khi loại khỏi phạm vi; nếu vẫn bắt buộc, dành công suất và điều chỉnh lịch Social/AI tương ứng.

## Nội dung có thể dùng để báo cáo tiến độ

Đến ngày 13/10/2026, đề tài đã có tài liệu phân tích yêu cầu, thiết kế kiến trúc, cơ sở dữ liệu và hợp đồng API; đã triển khai nền tảng xác thực, phiên đăng nhập và cổng quản trị tài khoản. Phân hệ tri thức đã có API hỏi đáp, bình chọn, quản lý phân loại và quy trình tải lên, phê duyệt, công bố tài liệu. Bộ kiểm thử Learning chạy lại đạt 5 bài kiểm thử với 79 kiểm tra trên môi trường kiểm thử cô lập. Các nội dung tiếp theo là nghiệm thu tích hợp trên trình duyệt, triển khai mạng xã hội và Android, thay đồ thị minh họa bằng Knowledge Graph thật, tích hợp AI và hoàn thiện báo cáo đánh giá. Mục tiêu nội bộ đề xuất là hoàn thiện trước 03/01/2027 để có thời gian dự phòng trước hạn 18/01/2027.

## Nguồn đối soát chính

- `docs/Base_du_an/IMPLEMENTATION_PROGRESS.md`: tổng hợp cũ ngày 29/09.
- `docs/specs/phase-0-foundation-identity/02-admin-portal-plan.md`: ADM-001–009 và bằng chứng ngày 27/09.
- `services/learning-service/routes/api.php`, `app/Http/Controllers/Api/LearningAdminController.php`, `app/Services/LearningOutbox.php`, `tests/Feature/LearningAdminApiTest.php`: lifecycle, permission, file và outbox.
- `services/social-service/routes/api.php`, `apps/web/src/views/modules/BaseInsideView.vue`: Social hiện còn khung/mock.
- `services/ai-knowledge-service/app/services/demo_graph.py`: graph fixture.
- `apps/mobile/README.md`: chưa có project Android.

Không tự chuyển task sang DONE trong lần đối soát này. Các bộ test khác, Gateway integration mới và browser E2E vẫn cần chạy trước khi đóng mốc tương ứng.
