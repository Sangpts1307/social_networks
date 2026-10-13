1. Cần kiểm tra gì trước?
Có 4 điểm chính:
1. Phạm vi: đọc IMPLEMENTATION_BASELINE.md để biết chức năng và công nghệ đã chốt.
2. Tiến độ: mở [Phase 0](/Users/sangpt/Desktop/ĐATN/social_networks/docs/specs/phase-0-foundation-identity/README.md), xem task nào cần rà soát, đang làm hoặc bị chặn.
3. Code và môi trường: xác nhận phần nào đã có, chạy được không, thiếu cấu hình hoặc dịch vụ nào.
4. Điều kiện bắt đầu: task phụ thuộc đã hoàn thành chưa; tiêu chí nào dùng để xác nhận task mới hoàn thành.
NEEDS_REVIEW nghĩa là chưa xác định chính xác tiến độ, nên cần kiểm tra trước khi viết thêm code.
2. Lần đầu tiên, gửi prompt này
Bắt đầu Phase 0 của dự án social_networks.

Đọc:
- docs/IMPLEMENTATION_BASELINE.md
- docs/specs/README.md
- docs/specs/phase-0-foundation-identity/README.md
- docs/specs/phase-0-foundation-identity/01-foundation.md

Đối soát các task Foundation với source và môi trường hiện tại:
- Phần nào đã có, phần nào còn thiếu.
- Các service, Gateway, Web và database có cấu hình chạy như thế nào.
- Chạy các kiểm tra phù hợp; không reset database hoặc xóa dữ liệu.
- Không đánh dấu DONE chỉ vì có file hoặc bộ khung.
- Cập nhật trạng thái, bằng chứng và phần còn thiếu vào file task.

Sau đó chọn nhóm task Foundation cần làm trước, hoàn thiện những phần
đã đủ điều kiện trong baseline và kiểm tra kết quả.
Nếu thiếu cấu hình hoặc quyết định bắt buộc, ghi rõ blocker.

Kết thúc bằng hướng dẫn chạy thử, danh sách task đã hoàn thành
và nhóm task nên làm tiếp.
3. Những lần tiếp theo, giao theo ID
Dùng mẫu ngắn này:
Thực hiện các task [ID-1, ID-2] trong [đường dẫn file phase].

Đọc baseline và spec liên quan; kiểm tra code hiện có và dependency.
Tận dụng phần đã làm, hoàn thiện phần còn thiếu trong phạm vi các task này.

Cập nhật IN_PROGRESS khi bắt đầu.
Sau khi kiểm tra đạt tiêu chí, cập nhật DONE kèm bằng chứng và nhật ký.
Nếu chưa hoàn thành, ghi rõ phần còn thiếu hoặc blocker.

Cuối cùng chỉ rõ cách tôi chạy thử và task nên làm tiếp.