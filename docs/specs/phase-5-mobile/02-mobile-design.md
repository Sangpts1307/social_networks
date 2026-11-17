# Thiết kế mobile — Flutter (Dart 3)

Quyết định chuẩn hóa ngày 2026-11-17. Chuyển đổi toàn diện từ React Native sang **Flutter** theo kiến trúc dự án chuẩn `rentid-app`. Hỗ trợ build và chạy độc lập trên cả Android và iOS bằng lệnh chuẩn `flutter run`.

## 1. Stack công nghệ và phạm vi

- **Framework:** Flutter SDK 3.x / Dart 3.
- **State Management:** Flutter Riverpod (`StateNotifierProvider`, `Provider`).
- **Networking:** Dio + Interceptors (`AuthInterceptor`, `LoggerInterceptor`).
- **Routing:** GoRouter (hỗ trợ nested shell route, URL navigation, redirect guard).
- **Secure Storage:** FlutterSecureStorage (lưu JWT token trên Android Keystore & iOS Keychain).
- **UI & Theme:** Material 3 + Cupertino Page Transitions; Tailwind/Facebook-inspired design system (`AppColors`, `AppTextStyles`, `AppTheme`).
- **Nền tảng mục tiêu:** Android (SDK 23+) và iOS (iOS 15.0+).

## 2. Cấu trúc dự án (Feature-First Clean MVVM)

Học tập cấu trúc dự án từ `rentid-app`:

```text
apps/mobile/
├── android/                   # Native Android config (Gradle KTS, minSdk 23, Internet permission)
├── ios/                       # Native iOS config (CocoaPods, iOS 15.0 target, Info.plist permissions)
├── lib/
│   ├── app.dart               # MaterialApp.router cấu hình theme và global error handler
│   ├── main.dart              # Entry point bọc ProviderScope
│   ├── core/
│   │   ├── config/            # EnvConfig (nhận diện URL động cho Android emulator 10.0.2.2 vs iOS localhost)
│   │   ├── error/             # Failures (ServerFailure, ValidationFailure, NetworkFailure, AuthFailure)
│   │   ├── network/           # DioClient, AuthInterceptor, LoggerInterceptor
│   │   ├── router/            # AppRoutes, AppRouter với Auth Guard tự động chuyển hướng
│   │   ├── storage/           # SecureStorageService (FlutterSecureStorage)
│   │   ├── theme/             # AppColors, AppTextStyles, AppTheme
│   │   ├── utils/             # Result<T>, Logger, Validators, DateTimeFormatter, Diacritics StringUtils
│   │   └── widgets/           # AppAvatar, AppButton, AppTextField, NoticeBanner, EmptyState
│   ├── data/
│   │   ├── models/            # Post, Channel, Comment, Chat, Story, User, Session, Notification, Knowledge
│   │   ├── services/          # AuthService, PostService, ChatService, NotificationService, LearningService, DemoData
│   │   └── repositories/      # AuthRepository, PostRepository, ChatRepository, NotificationRepository, LearningRepository
│   └── features/
│       ├── auth/              # LoginView, PasswordView, AuthViewModel
│       ├── home/              # MainShell (BottomNavigationBar 5 tabs: Feed, Channels, Chat, Notifications, Menu)
│       ├── feed/              # FeedView, FeedViewModel, PostComposer, StoryBar, CommentsSheet, PostCard
│       ├── channels/          # ChannelsView, ChannelDetailView
│       ├── chat/              # ChatListView, ChatDetailView, NewChatView
│       ├── notifications/     # NotificationsView (live API + demo fallback)
│       ├── menu/              # MenuView (Shortcut nhanh tới Profile, Library, Settings, Logout)
│       ├── profile/           # ProfileView (thông tin cá nhân, avatar, stats, bài viết đã đăng)
│       ├── library/           # LibraryView (tài liệu tri thức, Q&A)
│       └── search/            # SearchView (tìm kiếm tức thì, lọc người dùng, bài viết, kênh)
├── pubspec.yaml               # Flutter package configuration
└── test/
    └── widget_test.dart       # Widget & smoke tests
```

## 3. Đồng nhất API với Web VueJS và API Gateway

Toàn bộ dịch vụ mạng (`apps/mobile/lib/data/services/`) gọi qua API Gateway duy nhất, sử dụng URL và HTTP Method hoàn toàn đồng nhất với Web (`apps/web/src/api/`):

| Module | Chức năng | Endpoint Gateway | HTTP Method |
| --- | --- | --- | --- |
| **Auth** | Đăng nhập | `/v1/auth/login` | `POST` |
| | Kiểm tra phiên | `/v1/auth/session` | `GET` |
| | Đổi mật khẩu | `/v1/auth/password` | `POST` |
| | Đăng xuất | `/v1/auth/logout` | `POST` |
| | Hồ sơ cá nhân | `/v1/me/profile` | `GET` / `PATCH` |
| **Social** | Bảng tin bài viết | `/v1/social/posts` | `GET` (hỗ trợ channel_id, saved, q, post_type, cursor) |
| | Tạo bài viết | `/v1/social/posts` | `POST` |
| | Cập nhật bài | `/v1/social/posts/:id` | `PATCH` |
| | Xóa bài viết | `/v1/social/posts/:id` | `DELETE` |
| | Thả cảm xúc | `/v1/social/posts/:id/reaction` | `PUT` / `DELETE` |
| | Lưu bài viết | `/v1/social/posts/:id/save` | `PUT` / `DELETE` |
| | Danh sách kênh | `/v1/social/channels` | `GET` |
| | Tạo kênh mới | `/v1/social/channels` | `POST` |
| | Tham gia/Rời kênh | `/v1/social/channels/:id/membership` | `PUT` / `DELETE` |
| | Bình luận bài viết | `/v1/social/posts/:id/comments` | `GET` / `POST` |
| | Xóa bình luận | `/v1/social/comments/:id` | `DELETE` |
| | Thống kê theo dõi | `/v1/social/follow-stats` | `GET` |
| **Chat** | Danh sách hội thoại | `/v1/chat/conversations` | `GET` |
| | Tạo hội thoại | `/v1/chat/conversations` | `POST` |
| | Tin nhắn hội thoại | `/v1/chat/conversations/:id/messages` | `GET` / `POST` |
| | Đánh dấu đã đọc | `/v1/chat/conversations/:id/read` | `PUT` |
| | Danh bạ nhân viên | `/v1/directory/users` | `GET` |
| **Notification** | Danh sách thông báo | `/v1/notifications` | `GET` |
| | Đếm chưa đọc | `/v1/notifications/unread-count` | `GET` |
| | Đánh dấu 1 tin đã đọc| `/v1/notifications/:id/read` | `PUT` |
| | Đánh dấu tất cả đã đọc| `/v1/notifications/read-all` | `PUT` |
| | Đăng ký FCM token | `/v1/devices` | `POST` |
| **Learning** | Thư viện tri thức | `/v1/learning/knowledge-items` | `GET` |

## 4. Chạy & Build ứng dụng

1. **Khởi động ứng dụng bằng `flutter run`:**
   ```bash
   cd apps/mobile
   flutter run
   ```
2. **Chỉ định thiết bị cụ thể:**
   ```bash
   flutter run -d chrome            # Chạy web preview
   flutter run -d <ios_simulator>   # Chạy trên iOS Simulator
   flutter run -d <android_device>  # Chạy trên Android Emulator hoặc máy thật
   ```
3. **Truyền URL Gateway tùy chỉnh khi cần:**
   ```bash
   flutter run --dart-define=API_BASE_URL=http://192.168.1.100:8000/api
   ```
4. **Kiểm tra mã nguồn & Kiểm thử:**
   ```bash
   cd apps/mobile
   dart analyze
   flutter test
   ```
