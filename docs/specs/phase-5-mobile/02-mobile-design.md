# Thiết kế mobile — React Native

Quyết định chủ dự án ngày 2026-10-16. Trạng thái: đã triển khai source demo 2026-10-19; chưa nghiệm thu API thật/native.

## Stack và phạm vi

React Native + Expo + TypeScript; NativeWind 4 + Tailwind CSS 3 cho giao diện className. Android và iOS; tablet cần bố cục thích ứng và kiểm tra riêng. Web tiếp tục Vue 3. Chia sẻ API contract và kiến thức TypeScript, không dùng trực tiếp component Vue trong mobile.

## Cấu trúc khởi đầu

```text
apps/mobile/
├── App.tsx
├── src/
│   ├── screens/FeedScreen.tsx
│   ├── components/PostCard.tsx
│   ├── services/posts.ts
│   └── types/post.ts
├── global.css
├── babel.config.js
├── metro.config.js
├── tailwind.config.js
├── nativewind-env.d.ts
├── .env.example
└── package.json
```

Cây trên là mục tiêu, không tạo thư mục rỗng trước. App.tsx là entry point theo template blank-typescript, chưa dùng Expo Router. Chỉ thêm điều hướng khi triển khai nhiều màn hình. Chưa cần Redux hoặc nhiều tầng repository/use-case.

- screens: bố cục và trạng thái màn hình; gọi services trong effect/event handler, có cleanup khi rời màn hình.
- components: các khối trình bày nhỏ như PostCard, AppButton, Avatar; nhận dữ liệu qua props, không tự gọi API.
- services: fetch tới Gateway, xử lý HTTP error và auth theo contract thực tế; không gọi domain service trực tiếp.
- types: kiểu dữ liệu theo contract; TypeScript không tự kiểm tra JSON lúc runtime.
- NativeWind: className cho style thông thường; style cho giá trị động hoặc trường hợp cần thiết.

## Mẫu trình bày

```tsx
function PostCard({ post }: { post: Post }) {
  return (
    <View className="mb-3 rounded-xl bg-white p-4">
      <Text className="mb-2 text-base font-semibold">{post.authorName}</Text>
      <Text className="text-sm leading-6 text-gray-700">{post.content}</Text>
    </View>
  );
}
```

Post là kiểu minh họa, không phải xác nhận API có authorName. Các snippet cần import View/Text từ react-native và Post từ types khi đưa vào source.

```tsx
// Bên trong component nội dung; state được lấy từ luồng gọi API.
if (loading) return <ActivityIndicator />;
if (error) return <Text>{error}</Text>;
return (
  <FlatList
    data={posts}
    keyExtractor={(post) => post.id}
    renderItem={({ item }) => <PostCard post={item} />}
    ListEmptyComponent={<Text>Chưa có bài viết.</Text>}
  />
);
```

Không lồng nhiều toán tử điều kiện trong JSX. Màn hình chính gồm header và component nội dung. Danh sách dài dùng FlatList, hỗ trợ phân trang theo Gateway; bảo đảm loading/empty/error/retry và quyền truy cập. Bao ứng dụng bằng SafeAreaProvider/SafeAreaView; kiểm tra bàn phím, cỡ chữ, vùng bấm và screen reader.

## Tích hợp và release

- EXPO_PUBLIC_API_URL là cấu hình công khai, không chứa secret. Token nhạy cảm dùng Expo SecureStore; đăng xuất xóa phiên và thu hồi push token theo contract.
- HTTPS ở staging/production; WebSocket dùng kênh bảo mật. Realtime và push phải khớp backend, không tự chọn protocol mới. Android dùng FCM, iOS cần cấu hình APNs tương ứng.
- Expo Go cho kiểm tra giao diện ban đầu; development build cho push/native integration. Kiểm tra Android/iOS riêng.
- Backend triển khai lên server; app cài lên thiết bị. Tách local/staging/production; APK cài thử, AAB cho Google Play, bản iOS qua quy trình ký và phân phối của Apple.
- Danh sách màn hình MVP vẫn cần chốt theo MOB-001. Luồng đầu tiên đề xuất: đăng nhập → hồ sơ → feed; chưa coi là phạm vi nghiệm thu đã duyệt.

## Hướng dẫn

[Cài đặt và chạy](../../../apps/mobile/README.md). App đã khởi tạo tại apps/mobile theo cấu trúc này; backend giữ nguyên. Dự án dùng Expo SDK 57 để tương thích Expo Go 57 trên iPhone/Android (cập nhật 2026-10-21).


## Phạm vi demo Social hiện hành — 2026-10-19

Theo yêu cầu tiếp theo của chủ dự án, tập trung bản demo mạng xã hội trước. Điều hướng tại App.tsx → FeedScreen gồm Bảng tin, Kênh, Đã lưu, Cá nhân. LibraryScreen không nằm trong luồng này.

useSocial giữ state dùng chung; PostCard, PostComposer, CommentsSheet và social-ui tách giao diện thành component nhỏ. Demo fixture đặt tại services/social-demo.ts; DTO và API theo SocialController, PostController, InteractionController hiện có. Không tạo endpoint mới hoặc chuyển nghiệp vụ sang client thật.

Demo hỗ trợ đăng/sửa/xóa bài của mình, tìm bài đã tải/lọc kênh, cảm xúc, bình luận/trả lời một cấp, lưu bài, bài cá nhân và membership kênh mô phỏng. Xóa bài có xác nhận; gửi rỗng bị chặn; trạng thái loading/empty/error có UI. Membership thật, media, sửa/xóa bình luận, chat/push chưa thuộc bản demo. Dữ liệu demo chỉ nằm trong phiên.

Bằng chứng: [hướng dẫn và kịch bản smoke test](../../../apps/mobile/README.md), [ảnh demo](../../../apps/mobile/demo-preview.png). Kiểm tra native/API thật vẫn còn phải làm.


## Điều chỉnh giao diện theo phản hồi chủ dự án

Màu xanh dương, nền xám sáng, cấu trúc quen thuộc như Facebook; bám menu và dữ liệu web BaseInsideView.vue/mockData.js. Năm tab Bảng tin, Kênh, Tin nhắn, Thông báo, Menu thay bốn tab trước. Hồ sơ/Đã lưu qua Menu. SocialSections chứa Menu/Base Message/Thông báo và liên kết Base Square, Công ty, Sự kiện. Tin nhắn/thông báo mới chỉ là prototype local trong demo; backend thật chưa tích hợp. Ảnh minh họa dùng URL đã có ở web, không mở rộng API upload media. Typecheck/lint/export đạt; browser 390×844 đã kiểm tra.


## Tương tác tìm kiếm theo ảnh tham chiếu

Global SearchScreen là modal toàn màn hình kiểu Facebook: back, input, recent queries, see all/remove history, people suggestions; kết quả phân loại người/bài/kênh. Không dùng ô tìm bài inline dưới header nữa. Base Message có ô tìm riêng theo tên/vai trò/nội dung hội thoại. Chuẩn hóa Unicode để hỗ trợ tìm không dấu và nhiều từ khóa. Các kết quả có thể mở hồ sơ xem trước, hội thoại demo, bài viết hoặc kênh tương ứng. Người dùng thật chưa có danh bạ API tích hợp; chỉ dùng fixture trong demo hoặc dữ liệu đã tải ở phiên thật và ghi rõ phạm vi. Lịch sử chỉ trong bộ nhớ phiên. Kiểm tra UI browser và typecheck/lint/export đã đạt; chưa nghiệm thu native.


## Quyết định UI mới nhất — 2026-10-20

Kênh là một trang gợi ý ở trên, danh sách đã tham gia ở dưới; bỏ input tìm kiếm và các tab con. Composer học theo Facebook với Bài viết/Story, chọn ảnh/video từ thư viện, preview/xóa media, bài chỉ có media, dải Story và viewer. Demo Story chữ/nền hoặc một ảnh/video có TTL 24 giờ; bài demo tối đa 10 media, 100 MB/tệp. Media/story không có backend contract triển khai, nên chỉ dùng client state/cache trong demo; phiên thật báo chưa tích hợp, không gọi endpoint tự bịa hoặc gửi văn bản làm mất media. Backend nghiệp vụ giữ nguyên. Hai kênh gợi ý mẫu bổ sung chỉ nằm trong fixture. Xem README mobile để có bằng chứng UI và giới hạn. Typecheck/lint/doctor/export đạt, chưa nghiệm thu native/server.
