# OpenHello Mobile

React Native + Expo SDK 57 + TypeScript + NativeWind 4/Tailwind 3. Đã triển khai source demo Android/iOS ngày 2026-10-19.

## Chạy ngay

```bash
cd "/Users/sangpt/Desktop/ĐATN/social_networks/apps/mobile"
npm install
npm start
```

Quét QR bằng Expo Go tương thích SDK 57; điện thoại và Mac cùng Wi-Fi. Chọn **Khám phá bản demo** để xem không cần backend. Bản demo hiện tập trung mạng xã hội: Bảng tin, Kênh, Đã lưu và Cá nhân; đăng/sửa/xóa bài văn bản, bốn cảm xúc, bình luận và trả lời một cấp. Demo có nhãn dữ liệu mẫu, chỉ lưu trong bộ nhớ phiên; reload hoặc thoát demo sẽ xóa dữ liệu. Chat/push chưa triển khai.

Preview trình duyệt:

```bash
npm run web -- --port 8087
```

Mở http://localhost:8087. Đây là cùng source React Native qua react-native-web, không thay thế kiểm tra native. Giả lập đã cài có thể chạy `npm run android` hoặc `npm run ios`.

## Kết nối Gateway thật

Tạo .env từ .env.example và thay IP bằng IP LAN của Mac:

```dotenv
EXPO_PUBLIC_API_URL=http://192.168.1.10:8080/api
```

Không đặt secret trong EXPO_PUBLIC_*. Nếu không cấu hình, app lấy host của Expo và cổng Gateway 8080. Android emulator có thể dùng http://10.0.2.2:8080/api; iOS Simulator dùng http://127.0.0.1:8080/api.

Gateway mặc định bind 127.0.0.1. Để điện thoại LAN truy cập được, chạy Gateway với HOST=0.0.0.0 sau khi các backend đã chạy theo hướng dẫn repository:

```bash
cd "/Users/sangpt/Desktop/ĐATN/social_networks/gateway/api-gateway"
HOST=0.0.0.0 npm run start:dev
```

Đăng nhập bằng tài khoản hệ thống hiện có; không có mật khẩu mặc định trong mobile. Cần Gateway/Identity/Social/Learning cùng hạ tầng dữ liệu. Browser preview API thật cần thêm origin http://localhost:8087 vào ALLOWED_ORIGINS local của Gateway; native không bị CORS trình duyệt.

Các path nối sau API_URL (/api), dựa vào controller và API web hiện tại:

- POST /v1/auth/login; GET /v1/auth/session; POST /v1/auth/password; POST /v1/auth/logout.
- GET /v1/social/posts (cursor); GET /v1/social/channels; POST /v1/social/posts.
- PUT/DELETE /v1/social/posts/:id/reaction.
- GET /v1/learning/knowledge-items.

Token native dùng SecureStore, browser chỉ lưu trong bộ nhớ. Phiên 401 về đăng nhập; lỗi mạng lúc restore có nút thử lại. Logout chỉ xóa phiên sau server xác nhận; nếu lỗi báo thử lại. Đổi mật khẩu lần đầu chặn workspace.

Social API chưa trả tên tác giả nên hiển thị UUID rút gọn, không đoán tên. Luồng demo hiện chỉ dành cho mạng xã hội; màn hình/thư viện cũ còn source nhưng không nằm trong điều hướng. Danh sách kênh thật tải các trang theo cursor. Production dùng HTTPS; HTTP local không phải cấu hình production.

## Cấu trúc

```text
App.tsx                  # Phiên đăng nhập → workspace xã hội bốn tab
src/
  screens/               # Login, Password và workspace xã hội Feed
  hooks/                 # useSocial quản lý dữ liệu và các tương tác
  components/            # PostCard, Button, Field, Avatar, Notice
  services/              # Gateway, auth, posts, learning, demo fixtures
  types/                 # DTO theo API
```

Giữ App.tsx theo quyết định chủ dự án, chưa thêm Router/Redux. NativeWind đã cấu hình; Tailwind 3 mobile độc lập Tailwind 4 web.

## Kiểm tra

```bash
npm run typecheck
npm run lint
npx expo-doctor
npm run export
```

Kết quả 2026-10-19: TypeScript/lint đạt; doctor 20/20; export bundle Android/iOS/web đạt. Smoke browser 390x844 đạt: demo, đăng/sửa/xóa bài, cảm xúc, lưu bài qua tab, bình luận/trả lời, tìm rỗng, tham gia/rời/lọc kênh, hồ sơ. Kiểm tra đăng nhập và thoát demo đã thực hiện ở bản nền trước. Gateway chưa chạy nên chưa kiểm tra API thật end-to-end. Chưa chạy native simulator/điện thoại, chưa tạo APK/IPA. Export bundle không phải native build có ký.

## iOS và bản cài

Dự án dùng SDK 57 để tương thích Expo Go 57 trên điện thoại. Có thể dùng Expo Go trên iPhone thật hoặc EAS cloud để build thay vì nâng Mac ngay. Kiểm tra tương thích SDK/runtime trước build.

EAS (cần Expo account, signing theo nền tảng):

```bash
npx eas-cli@latest build:configure
npx eas-cli@latest build --platform android --profile preview
npx eas-cli@latest build --platform ios --profile preview
```

Preview Android tạo APK; preview iOS dành cho simulator, không cài iPhone thật. TestFlight/App Store cần Apple Developer và cấu hình production. com.openhello.mobile là bundle identifier khởi đầu, cần chốt trước phát hành.

Nguồn: [Expo SDK 57](https://docs.expo.dev/versions/v57.0.0/), [NativeWind](https://www.nativewind.dev/docs/getting-started/installation).

Dependency audit 2026-10-19 còn 33 cảnh báo (11 moderate, 22 high), chủ yếu chuỗi dependency Expo/Metro/React Native/NativeWind. Đề xuất tự động của npm đổi major hoặc hạ SDK, nên không chạy audit fix --force làm lệch bộ thư viện tương thích. Cần xử lý/đánh giá riêng trước phát hành production; bản này là demo đã kiểm tra bundle và UI.


## Demo mạng xã hội — cập nhật 2026-10-19

Bốn tab: **Bảng tin / Kênh / Đã lưu / Cá nhân**. Không đưa Learning vào luồng demo hiện tại.

- Bảng tin có tìm nội dung trong các bài đã tải, lọc theo kênh, phân trang API thật, kéo để tải lại.
- Đăng bài vào kênh; nút đăng vô hiệu khi nội dung rỗng. Bài của mình có menu sửa/xóa và bước xác nhận xóa. Không hiện nút sửa/xóa cho người khác; API vẫn là nơi quyết định quyền thật.
- Cảm xúc: like/love/sad/angry, đổi/bỏ cảm xúc và cập nhật bộ đếm. Nút mũi tên cạnh cảm xúc hỗ trợ cả web và điện thoại, ngoài thao tác nhấn giữ.
- Bình luận hiển thị trong sheet, gửi bình luận gốc/trả lời một cấp, bộ đếm cập nhật, tải thêm theo cursor khi dùng API thật. Bài khóa bình luận chặn ô nhập. Chưa có sửa/xóa bình luận.
- Lưu/bỏ lưu; tab Đã lưu dùng cùng trạng thái. Cá nhân hiển thị bài của mình và đăng xuất.
- Kênh: xem bài theo kênh; tham gia/rời kênh hiện chỉ mô phỏng trong demo. Demo chỉ cho đăng/sửa, thả cảm xúc và bình luận khi đã tham gia kênh. Chế độ thật chưa có nút quản lý membership.
- Demo có tên/vai trò minh họa theo user ID riêng, ba kênh, bốn bài viết và hội thoại mẫu. Dữ liệu và bộ đếm giữ nguyên khi chuyển tab; không lưu qua reload/thoát demo. Không ghi dữ liệu mẫu vào backend.

API bổ sung: PATCH/DELETE /v1/social/posts/:id, PUT/DELETE /v1/social/posts/:id/save, GET/POST /v1/social/posts/:id/comments. Request API thật theo controller Social hiện có, không gọi service trực tiếp.

Kịch bản trình diễn:

1. Khám phá bản demo → mở bộ chọn cảm xúc, chọn Yêu thích.
2. Lưu bài → tab Đã lưu → mở bình luận → trả lời đồng nghiệp.
3. Bảng tin → tạo bài → Cá nhân → sửa bài → xác nhận xóa bài vừa tạo.
4. Kênh → rời/tham gia lại → xem bài trong kênh.
5. Tìm một từ không tồn tại → kiểm tra trạng thái rỗng → xóa tìm kiếm.

Đã smoke test toàn bộ các bước này qua web preview ở 390×844, kiểm tra dữ liệu giữ nguyên giữa các tab. TypeScript/lint đạt. Chưa nghiệm thu trên điện thoại native hoặc backend thật; không coi bản demo là hoàn thành toàn bộ Social mobile. Chưa có media upload, chat, push hoặc nghiệp vụ quản trị mobile.


## Thiết kế theo web và Facebook — cập nhật mới nhất

Theo yêu cầu chủ dự án: dùng nền xám sáng #f0f2f5, bài viết nền trắng, xanh dương #1877f2/Tailwind blue làm màu nhấn. Bỏ hero trang trí, giảm bo góc, thu gọn composer, avatar tròn, bài viết có ảnh minh họa theo web. Toàn bộ màn hình mobile đã bỏ accent tím.

Điều hướng hiện tại: **Bảng tin / Kênh / Tin nhắn / Thông báo / Menu**. Hồ sơ và Đã lưu nằm trong Menu; avatar bảng tin mở hồ sơ. Base Inside, Base Message, Base Square, Kênh nội bộ, Công ty và Sự kiện được tổ chức theo BaseInsideView.vue và dữ liệu mẫu mockData.js trên web.

- Tin nhắn: hội thoại từ bản thiết kế web, gửi thử chỉ lưu local trong phiên demo; chưa nối backend chat/realtime.
- Thông báo: fixture trong demo, đánh dấu đã đọc và mở bài liên quan; chưa nối Notification API/push.
- Base Square: mở LibraryScreen metadata đã có. Thông tin công ty giữ mục điều hướng, chưa có nội dung chính thức. Sự kiện dùng lịch minh họa trong web.
- Bài viết/ảnh/người dùng minh họa lấy theo web; ảnh demo là URL Unsplash đã có trong web, cần Internet. Avatar có fallback chữ và ảnh bài lỗi tải sẽ được ẩn. Chưa triển khai upload ảnh của người dùng.
- Các tương tác bài viết, bình luận, lưu, kênh và API Gateway giữ lại. Demo chat/thông báo không chạy khi đăng nhập thật; màn hình giải thích chưa tích hợp thay vì giả dữ liệu thật.

Kiểm tra: TypeScript/lint đạt; export Android/iOS/web đạt. Browser 390×844 đã kiểm tra bảng tin/ảnh, Menu, hội thoại demo gửi tin local và thông báo mở đúng bài. Ảnh bàn giao: demo-preview.png, menu-preview.png. Chưa kiểm tra thiết bị native/API thật.


## Tìm kiếm như Facebook / Messenger

Nút kính lúp mở SearchScreen toàn màn hình, không chèn ô tìm kiếm vào bảng tin. Có nút quay lại, lịch sử gần đây (tối đa 20 từ khóa, chỉ trong phiên), xem tất cả/xóa từng mục/xóa tất cả, gợi ý đồng nghiệp và khám phá kênh. Gõ từ khóa hiển thị các bộ lọc Tất cả/Mọi người/Bài viết/Kênh. Chọn đồng nghiệp mở thông tin; chọn Nhắn tin chuyển sang đúng hội thoại demo. Chọn bài mở bình luận, chọn kênh mở bảng tin kênh.

Base Message có ô tìm tên người, vai trò và nội dung tin nhắn trong các hội thoại demo; hỗ trợ không dấu, chữ hoa/thường và nhiều từ khóa. Có nút xóa và trạng thái không có kết quả. Không giả lập tìm kiếm người thật: chế độ thật chỉ tìm dữ liệu đang tải; danh bạ và chat server chưa tích hợp. Lịch sử không lưu qua reload/thoát demo.

Source: components/SearchScreen.tsx, components/SocialSections.tsx, services/search.ts. Đã kiểm tra browser: luan → Võ Thành Luân → hội thoại; Messenger phat → Phạm Phát; Knowledge Graph → bài tương ứng; ghi nhận/xem tất cả/xóa lịch sử. Kiểm tra matcher tiếng Việt 4 trường hợp đạt; TypeScript/lint/export Android/iOS/web đạt. Ảnh: search-preview.png và messenger-search-preview.png. Chưa nghiệm thu bàn phím/native trên thiết bị.


Cập nhật giao diện hội thoại: bỏ tiêu đề Base Message/mô tả phụ và dòng giới thiệu demo dài; danh sách nền trắng liền, không card/gutter rời. Avatar 58px dùng ảnh từ fixture web, preview một dòng, thời gian không xuống hàng và dấu chưa đọc. Mở hội thoại đánh dấu đã đọc; tìm kiếm tên và gửi demo giữ nguyên. Typecheck/lint và kiểm tra browser 390×844 đạt.

## Kênh theo bố cục Facebook Groups

ChannelsScreen thay các card lớn bằng header Kênh, tìm kiếm tên/chủ đề (không dấu), hai mục Kênh của bạn và Khám phá. Danh sách của bạn dùng ảnh vuông + tên + mô tả ngắn; Khám phá dùng ảnh bìa và CTA tham gia/xem. Menu ba chấm chứa xem bài/rời kênh, rời có xác nhận. Membership vẫn chỉ mô phỏng trong demo; phiên thật chỉ hiển thị Khám phá, không giả thông tin đã tham gia. Ảnh dùng các URL có sẵn trong thiết kế web và fallback icon khi lỗi tải. Đã kiểm tra browser 390x844, đổi mục, tìm “tech” ra một kênh; TypeScript/lint đạt. Ảnh: channels-preview.png.


## Cập nhật 2026-10-20 — Kênh, bài viết media và Story

Phần Kênh bỏ hoàn toàn tìm kiếm và tab Khám phá/Kênh của bạn. Một màn hình gồm gợi ý chưa tham gia dạng ngang ở trên, kênh đã tham gia dạng danh sách ở dưới. Nhấn Tham gia chuyển kênh từ gợi ý xuống danh sách ngay; rời có xác nhận và đưa trở lại gợi ý. Demo thêm hai kênh gợi ý thiết kế/văn hóa, không ghi backend. Phiên thật chưa biết membership nên không giả danh sách đã tham gia.

Composer có hai chế độ Bài viết / Story. Bài viết cho chọn tối đa 10 ảnh/video, xem trước, xóa tệp và đăng có hoặc không có chữ. Media của bài demo có thể xem lại trên feed, video có native controls để phát/dừng. Story cho chữ + nền màu hoặc một ảnh/video kèm chữ; dải tin có nút Tạo tin, xem toàn màn hình và chuyển tin bằng nút trước/sau. Story có expires_at 24 giờ, lọc khỏi dải tin khi hết hạn (kiểm tra mỗi 30 giây). Nội dung và tệp chỉ nằm trong bộ nhớ/cache của phiên, reload/thoát sẽ mất; không phải kho lưu trữ hoặc upload production.

API backend hiện chỉ hỗ trợ bài văn bản, chưa có upload media/Story. App chặn đăng media/Story lên phiên thật với thông báo rõ ràng, không im lặng bỏ tệp; đăng bài văn bản thật giữ nguyên. Chưa có camera/quay video, chỉnh sửa video, lưu nháp hoặc tự chuyển Story theo thời lượng. Giới hạn 100 MB mỗi tệp để demo ổn định; codec phát được tùy nền tảng.

Dependency mới: expo-image-picker và expo-video theo SDK 57. Dùng system gallery cho ảnh/video; app chỉ xin quyền thư viện khi cần trên iOS. Plugin không bật camera/microphone vì chưa làm tính năng quay/chụp. Development build cũ cần build lại khi bổ sung native packages; Expo Go phải tương thích SDK 57.

Kiểm tra ngày 2026-10-20:

- TypeScript/lint đạt; Expo doctor 20/20; export Android/iOS/web đạt.
- Browser 390×844: chọn icon.png của project và video MP4 2 giây được tạo tổng hợp, đăng bài không chữ, thấy cả ảnh/video ở feed. Video duration=2, readyState=4 và paused=false sau nhấn phát.
- Tạo Story chữ đổi nền, đăng và xem lại; chọn video cho Story và đăng thành công. Story mẫu ảnh cũng xuất hiện trong dải tin.
- Không còn tìm kiếm ở màn Kênh; gợi ý nằm trên; nhấn tham gia chuyển số kênh đã tham gia từ 3 lên 4.
- Chưa kiểm tra trên điện thoại native hoặc upload lên server thật.

Ảnh bàn giao: channels-preview.png và story-composer-preview.png.


### Đồng bộ Expo Go — 2026-10-21

Đưa dự án về SDK 57 và đồng bộ các thư viện theo `expo install --fix`. Dừng Metro cũ rồi chạy `npx expo start --go --clear`; iPhone và Android dùng chung Wi-Fi với Mac. Dùng Expo Go 57 và chọn Khám phá bản demo. Chưa xác nhận chạy trực tiếp trên điện thoại thật trong môi trường phát triển này.

Xác minh sau đồng bộ: Expo 57.0.30, React Native 0.86.3, React 19.2.3; typecheck và lint đạt; Expo Doctor 21/21; export bundle iOS, Android, web thành công. Đây là kiểm tra đóng gói, chưa thay cho chạy thử trên thiết bị thật.
