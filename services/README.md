# Services

Mỗi thư mục con là một service triển khai và sở hữu dữ liệu độc lập. Quy tắc đầy đủ nằm trong [`../Cấu trúc cuối cùng.md`](../Cấu%20trúc%20cuối%20cùng.md).

- Các core service dùng PHP Laravel.
- AI Knowledge và Search có thể dùng Python.
- Không service nào được truy cập trực tiếp database của service khác.
- Giao tiếp đồng bộ qua REST, bất đồng bộ qua RabbitMQ.
- Chưa tạo test trong giai đoạn dựng base.
