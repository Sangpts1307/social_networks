# Openhello - Flutter Application

Ứng dụng di động **Openhello** đa nền tảng (**iOS & Android**) cho mạng xã hội doanh nghiệp nội bộ. Ứng dụng được xây dựng trên nền tảng **Flutter & Dart**, kế thừa và học tập cấu trúc dự án từ `rentid-app` (Feature-First Clean Architecture + Riverpod + Repository Pattern).

---

## 1. Công nghệ & Thư viện phê duyệt (Tech Stack)

| Hạng mục | Công nghệ / Thư viện | Ghi chú |
|---|---|---|
| **Framework & Ngôn ngữ** | Flutter (Stable 3.47.8+), Dart (^3.13.7+) | Null safety, Material 3, Cupertino transitions |
| **Quản lý trạng thái (State)** | `flutter_riverpod` (v2.6.1) | `StateNotifierProvider` & `StateNotifier` theo kiến trúc MVVM |
| **Networking (HTTP Client)** | `dio` (v5.8.0+) | Singleton instance với `AuthInterceptor` & `LoggerInterceptor` |
| **Điều hướng (Routing)** | `go_router` (v18.1.1+) | Declarative routing với Auth redirect guards |
| **Bảo mật & Lưu trữ phiên** | `flutter_secure_storage` (v10.3.4) | Lưu trữ mã hóa access token và session an toàn |
| **Cài đặt cục bộ** | `shared_preferences` (v2.5.2) | Lưu cấu hình giao diện & sở thích người dùng |
| **Đa phương tiện** | `image_picker` (v1.2.4) | Chọn ảnh / video khi đăng bài viết và tạo tin (Story) |
| **Testing & Linter** | `flutter_test`, `flutter_lints` | Kiểm thử giao diện và đảm bảo 0 cảnh báo linter |

---

## 2. Cấu trúc thư mục dự án (Project Architecture)

Cấu trúc tuân thủ mô hình **Feature-First Clean MVVM + Repository Pattern** tương tự `rentid-app`:

```text
apps/mobile/
├── android/                    # Mã nguồn cấu hình Android (build.gradle.kts, permissions, cleartext)
├── ios/                        # Mã nguồn cấu hình iOS (Podfile, Info.plist, permissions camera/photo)
├── assets/images/              # Hình ảnh, biểu tượng ứng dụng
├── test/                       # Unit & Widget tests
└── lib/
    ├── main.dart               # Entry point, FlutterBinding, error boundaries, ProviderScope
    ├── app.dart                # MaterialApp.router, ThemeData, GoRouter integration
    ├── core/                   # Các tiện ích và module dùng chung toàn hệ thống
    │   ├── config/             # Cấu hình môi trường (EnvConfig.apiBaseUrl, dynamic platform URL)
    │   ├── error/              # Định nghĩa lỗi (Failure, ServerFailure, NetworkFailure, AuthFailure)
    │   ├── network/            # Dio client trung tâm và Interceptors (Auth, Logger)
    │   ├── storage/            # SecureStorageService lưu trữ an toàn token
    │   ├── router/             # AppRoutes và AppRouter (GoRouter với Auth guard)
    │   ├── theme/              # Design tokens (AppColors, AppTextStyles, AppTheme)
    │   ├── utils/              # Form validators, DateTimeFormatter, StringUtils tiếng Việt
    │   └── widgets/            # Reusable UI widgets (AppAvatar, AppButton, AppTextField, NoticeBanner, EmptyState)
    ├── data/                   # Tầng dữ liệu trung gian
    │   ├── models/             # DTOs & Domain Models (User, Session, Post, Channel, Comment, Story, Chat, Notification, Knowledge)
    │   ├── services/           # HTTP services kết nối Gateway API (Auth, Post, Chat, Notification, Learning)
    │   └── repositories/       # Concrete Repositories (Auth, Post, Chat, Notification, Learning)
    └── features/               # Các module chức năng theo từng feature
        ├── auth/               # Đăng nhập (LoginScreen), Đổi mật khẩu bắt buộc (ChangePasswordScreen)
        ├── home/               # MainScaffoldScreen chứa thanh điều hướng 5 tabs
        ├── feed/               # Bảng tin (FeedView, PostCard, StoriesRail, StoryViewer, Composer, Comments)
        ├── channels/           # Quản lý kênh (ChannelsView, CreateChannelDialog, Join/Leave)
        ├── chat/               # Trò chuyện (ChatListView, ChatDetailScreen, NewDirectChat, CreateGroup)
        ├── notifications/      # Thông báo nội bộ (NotificationsView)
        ├── menu/               # Menu tùy chọn và quản lý cá nhân (MenuView)
        ├── profile/            # Trang hồ sơ cá nhân và bài viết của tôi (ProfileScreen)
        ├── library/            # Thư viện tri thức (LibraryScreen - Base Library)
        └── search/             # Tìm kiếm toàn cục bài viết, kênh, đồng nghiệp (SearchScreen)
```

---

## 3. Luồng dữ liệu (Data Flow)

Dữ liệu di chuyển theo 1 chiều duy nhất:
```text
UI (View) -> ViewModel (StateNotifier) -> Repository -> Service -> Gateway API
```
- Đăng nhập bằng tài khoản trên web. Mobile đọc và ghi dữ liệu qua cùng API Gateway.
- **Chế độ Kết nối API thật:** Gọi qua Gateway proxy (mặc định `http://localhost:8000/api` trên iOS/macOS và `http://10.0.2.2:8000/api` trên Android emulator).

---

## 4. Hướng dẫn cài đặt & Khởi chạy (Build & Run)

### 4.1. Cài đặt dependencies
```bash
cd apps/mobile
flutter pub get
```

### 4.2. Khởi chạy ứng dụng bằng `flutter run`

- **Chạy trên thiết bị kết nối mặc định (iOS Simulator / Android Emulator / macOS Desktop):**
  ```bash
  flutter run
  ```

- **Chạy và chỉ định API Backend cụ thể:**
  ```bash
  flutter run --dart-define=API_BASE_URL=http://localhost:8000/api
  ```

- **Chạy trên iOS Simulator:**
  ```bash
  flutter run -d iPhone
  ```

- **Chạy trên Android Emulator:**
  ```bash
  flutter run -d android
  ```

- **Chạy trên macOS Desktop (phục vụ test nhanh giao diện):**
  ```bash
  flutter run -d macos
  ```

---

## 5. Kiểm thử & Phân tích tĩnh (Analyze & Test)

- **Phân tích mã nguồn tĩnh (Linter):**
  ```bash
  dart analyze
  ```
  *(Đạt 0 lỗi, 0 cảnh báo linter).*

- **Chạy bộ kiểm thử (Widget / Unit Tests):**
  ```bash
  flutter test
  ```

---

## 6. Đóng gói ứng dụng (Build Release)

- **Build APK (Android):**
  ```bash
  flutter build apk --release
  ```

- **Build iOS Simulator:**
  ```bash
  flutter build ios --simulator --no-codesign
  ```

- **Build iOS IPA / Archive:**
  ```bash
  flutter build ipa --release
  ```
