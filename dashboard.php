
<?php
session_start();
require_once "pdo.php";

// =====================================
// Authentication and Account Verification
// =====================================

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare(
    "SELECT user_id, full_name, email, `password`,
            role, account_status, club_name,
            preferred_language
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user ||
    $user["account_status"] !== "approved" ||
    !in_array($user["role"], ["student", "organizer"], true) ||
    $user["role"] !== ($_SESSION["role"] ?? "")) {

    $_SESSION = [];
    session_regenerate_id(true);
    header("Location: login.php");
    exit;
}

$_SESSION["full_name"] = $user["full_name"];

$is_organizer = $user["role"] === "organizer";

$lang = in_array(
    $user["preferred_language"],
    ["en", "ms", "zh"],
    true
) ? $user["preferred_language"] : "en";

// =====================================
// Language Translations
// =====================================

$translations = [
    "en" => [
        "dashboard" => "Dashboard",
        "workspace" => "WORKSPACE",
        "account" => "ACCOUNT",
        "my_events" => "My Events",
        "create_event" => "Create Event",
        "edit_club" => "Edit Club Profile",
        "browse" => "Browse Events",
        "registrations" => "My Registrations",
        "explore" => "Explore Clubs",
        "followed" => "My Followed Clubs",
        "notifications" => "My Notifications",
        "profile" => "My Profile",
        "language" => "Language",
        "password" => "Change Password",
        "help" => "Help & FAQ",
        "logout" => "Log Out",
        "organizer" => "ORGANIZER PORTAL",
        "student" => "STUDENT PORTAL",
        "welcome" => "Welcome back",
        "organizer_desc" => "Organize events, manage participants, and connect with students.",
        "student_desc" => "Discover events, follow clubs, and manage your participation.",
        "your_workspace" => "Your Workspace",
        "open" => "Open",
        "profile_title" => "Personal Information",
        "profile_desc" => "Update the name displayed on your Agora account.",
        "full_name" => "Full Name",
        "email" => "Email Address",
        "role" => "Account Type",
        "club_name" => "Club Name",
        "save_profile" => "Save Changes",
        "language_title" => "Language Preferences",
        "language_desc" => "Choose your preferred language for the dashboard and account settings.",
        "select_language" => "Preferred Language",
        "save_language" => "Save Language",
        "language_note" => "Other Agora pages will remain in English until additional translations are added.",
        "password_title" => "Account Security",
        "password_desc" => "Change your password to keep your account secure.",
        "current_password" => "Current Password",
        "new_password" => "New Password",
        "confirm_password" => "Confirm New Password",
        "save_password" => "Update Password",
        "password_note" => "Use at least 8 characters. Do not share your password.",
        "help_title" => "Help & Frequently Asked Questions",
        "help_desc" => "Find answers to common Agora questions.",
        "saved" => "Your changes have been saved successfully.",
        "invalid_name" => "Enter a name between 1 and 100 bytes.",
        "invalid_language" => "Please select a valid language.",
        "wrong_password" => "Your current password is incorrect.",
        "password_length" => "Your new password must be between 8 and 72 bytes.",
        "password_mismatch" => "Your new passwords do not match.",
        "invalid_request" => "Invalid request.",
        "error_save" => "Unable to save your changes. Please try again.",
        "profile_note" => "Your email address and account type cannot be changed here."
    ],
    "ms" => [
        "dashboard" => "Papan Pemuka",
        "workspace" => "RUANG KERJA",
        "account" => "AKAUN",
        "my_events" => "Acara Saya",
        "create_event" => "Cipta Acara",
        "edit_club" => "Edit Profil Kelab",
        "browse" => "Cari Acara",
        "registrations" => "Pendaftaran Saya",
        "explore" => "Terokai Kelab",
        "followed" => "Kelab Diikuti",
        "notifications" => "Pemberitahuan Saya",
        "profile" => "Profil Saya",
        "language" => "Bahasa",
        "password" => "Tukar Kata Laluan",
        "help" => "Bantuan & Soalan Lazim",
        "logout" => "Log Keluar",
        "organizer" => "PORTAL PENGANJUR",
        "student" => "PORTAL PELAJAR",
        "welcome" => "Selamat kembali",
        "organizer_desc" => "Urus acara, peserta dan komuniti pelajar anda.",
        "student_desc" => "Terokai acara, ikuti kelab dan urus penyertaan anda.",
        "your_workspace" => "Ruang Kerja Anda",
        "open" => "Buka",
        "profile_title" => "Maklumat Peribadi",
        "profile_desc" => "Kemas kini nama yang dipaparkan dalam Agora.",
        "full_name" => "Nama Penuh",
        "email" => "Alamat E-mel",
        "role" => "Jenis Akaun",
        "club_name" => "Nama Kelab",
        "save_profile" => "Simpan Perubahan",
        "language_title" => "Pilihan Bahasa",
        "language_desc" => "Pilih bahasa untuk papan pemuka dan tetapan akaun.",
        "select_language" => "Bahasa Pilihan",
        "save_language" => "Simpan Bahasa",
        "language_note" => "Halaman Agora yang lain masih menggunakan bahasa Inggeris.",
        "password_title" => "Keselamatan Akaun",
        "password_desc" => "Tukar kata laluan untuk melindungi akaun anda.",
        "current_password" => "Kata Laluan Semasa",
        "new_password" => "Kata Laluan Baharu",
        "confirm_password" => "Sahkan Kata Laluan Baharu",
        "save_password" => "Kemas Kini Kata Laluan",
        "password_note" => "Gunakan sekurang-kurangnya 8 aksara.",
        "help_title" => "Bantuan & Soalan Lazim",
        "help_desc" => "Jawapan kepada soalan lazim tentang Agora.",
        "saved" => "Perubahan berjaya disimpan.",
        "invalid_name" => "Masukkan nama antara 1 hingga 100 bait.",
        "invalid_language" => "Sila pilih bahasa yang sah.",
        "wrong_password" => "Kata laluan semasa tidak betul.",
        "password_length" => "Kata laluan baharu mestilah antara 8 hingga 72 bait.",
        "password_mismatch" => "Kata laluan baharu tidak sepadan.",
        "invalid_request" => "Permintaan tidak sah.",
        "error_save" => "Perubahan tidak dapat disimpan.",
        "profile_note" => "Alamat e-mel dan jenis akaun tidak boleh diubah di sini."
    ],
    "zh" => [
        "dashboard" => "仪表板",
        "workspace" => "工作空间",
        "account" => "账户管理",
        "my_events" => "我的活动",
        "create_event" => "创建活动",
        "edit_club" => "编辑社团资料",
        "browse" => "浏览活动",
        "registrations" => "我的报名",
        "explore" => "探索社团",
        "followed" => "已关注的社团",
        "notifications" => "我的通知",
        "profile" => "个人资料",
        "language" => "语言设置",
        "password" => "修改密码",
        "help" => "帮助与常见问题",
        "logout" => "退出登录",
        "organizer" => "组织者中心",
        "student" => "学生中心",
        "welcome" => "欢迎回来",
        "organizer_desc" => "管理活动、参与者，并与学生保持联系。",
        "student_desc" => "探索校园活动、关注社团并管理报名。",
        "your_workspace" => "我的工作空间",
        "open" => "打开",
        "profile_title" => "个人信息",
        "profile_desc" => "修改你在 Agora 显示的姓名。",
        "full_name" => "姓名",
        "email" => "电子邮箱",
        "role" => "账户类型",
        "club_name" => "社团名称",
        "save_profile" => "保存修改",
        "language_title" => "语言偏好",
        "language_desc" => "选择仪表板和账户设置使用的语言。",
        "select_language" => "首选语言",
        "save_language" => "保存语言",
        "language_note" => "Agora 的其他页面暂时仍使用英语。",
        "password_title" => "账户安全",
        "password_desc" => "修改密码以保护你的账户。",
        "current_password" => "当前密码",
        "new_password" => "新密码",
        "confirm_password" => "确认新密码",
        "save_password" => "更新密码",
        "password_note" => "请使用至少 8 个字符的密码。",
        "help_title" => "帮助与常见问题",
        "help_desc" => "查找 Agora 常见问题的答案。",
        "saved" => "修改已成功保存。",
        "invalid_name" => "姓名长度必须在 1 到 100 字节之间。",
        "invalid_language" => "请选择有效的语言。",
        "wrong_password" => "当前密码不正确。",
        "password_length" => "新密码长度必须在 8 到 72 字节之间。",
        "password_mismatch" => "两次输入的新密码不一致。",
        "invalid_request" => "请求无效。",
        "error_save" => "无法保存修改，请重试。",
        "profile_note" => "邮箱地址和账户类型不能在这里修改。"
    ]
];

function h($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

function t($key) {
    global $translations, $lang;

    return $translations[$lang][$key]
        ?? $translations["en"][$key]
        ?? $key;
}

// =====================================
// Current Dashboard Section
// =====================================

$allowed_sections = [
    "home", "profile", "language",
    "password", "help"
];

$section = $_GET["section"] ?? "home";

if (!in_array($section, $allowed_sections, true)) {
    $section = "home";
}

// CSRF token for account changes
if (!isset($_SESSION["dashboard_token"])) {
    $_SESSION["dashboard_token"] =
        bin2hex(random_bytes(32));
}

$error = "";

// =====================================
// Handle Account Updates
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["token"] ?? "";
    $action = $_POST["action"] ?? "";

    if (!is_string($token) ||
        !hash_equals(
            $_SESSION["dashboard_token"],
            $token
        )) {
        http_response_code(403);
        die("Invalid security token.");
    }

    if ($section === "profile" &&
        $action === "update_profile") {

        $name = $_POST["full_name"] ?? "";

        if (!is_string($name)) {
            $error = t("invalid_name");
        } else {
            $name = trim($name);

            if ($name === "" || strlen($name) > 100) {
                $error = t("invalid_name");
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET full_name = ?
                         WHERE user_id = ?
                         AND role = ?
                         AND account_status = 'approved'"
                    );

                    $stmt->execute([
                        $name, $user_id, $user["role"]
                    ]);

                    $_SESSION["full_name"] = $name;

                    $_SESSION["dashboard_flash"] =
                        t("saved");

                    header(
                        "Location: dashboard.php?section=profile",
                        true,
                        303
                    );
                    exit;

                } catch (PDOException $e) {
                    $error = t("error_save");
                }
            }
        }

    } elseif ($section === "language" &&
              $action === "update_language") {

        $new_lang = $_POST["language"] ?? "";

        if (!is_string($new_lang) ||
            !in_array(
                $new_lang,
                ["en", "ms", "zh"],
                true
            )) {

            $error = t("invalid_language");

        } else {

            try {
                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET preferred_language = ?
                     WHERE user_id = ?
                     AND role = ?
                     AND account_status = 'approved'"
                );

                $stmt->execute([
                    $new_lang, $user_id, $user["role"]
                ]);

                header(
                    "Location: dashboard.php?section=language",
                    true,
                    303
                );
                exit;

            } catch (PDOException $e) {
                $error = t("error_save");
            }
        }

    } elseif ($section === "password" &&
              $action === "update_password") {

        $current = $_POST["current_password"] ?? "";
        $new = $_POST["new_password"] ?? "";
        $confirm = $_POST["confirm_password"] ?? "";

        if (!is_string($current) ||
            !is_string($new) ||
            !is_string($confirm)) {

            $error = t("invalid_request");

        } elseif (!password_verify(
            $current,
            $user["password"]
        )) {

            $error = t("wrong_password");

        } elseif (
            strlen($new) < 8 ||
            strlen($new) > 72
        ) {

            $error = t("password_length");

        } elseif ($new !== $confirm) {

            $error = t("password_mismatch");

        } else {

            try {
                $hash = password_hash(
                    $new,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET `password` = ?
                     WHERE user_id = ?
                     AND role = ?
                     AND account_status = 'approved'
                     AND `password` = ?"
                );

                $stmt->execute([
                    $hash,
                    $user_id,
                    $user["role"],
                    $user["password"]
                ]);

                if ($stmt->rowCount() !== 1) {
                    $error = t("error_save");
                } else {
                    $_SESSION["dashboard_flash"] =
                        t("saved");

                    session_regenerate_id(true);

                    header(
                        "Location: dashboard.php?section=password",
                        true,
                        303
                    );
                    exit;
                }

            } catch (PDOException $e) {
                $error = t("error_save");
            }
        }

    } else {
        $error = t("invalid_request");
    }
}

// Show flash message once
$flash = $_SESSION["dashboard_flash"] ?? "";
unset($_SESSION["dashboard_flash"]);

// =====================================
// Sidebar Navigation
// =====================================

if ($is_organizer) {

    $workspace_links = [
        ["my_events", "my_events.php"],
        ["create_event", "add.php"],
        ["edit_club", "edit_club.php"]
    ];

    $dashboard_cards = [
        ["my_events", "my_events.php", "01"],
        ["create_event", "add.php", "02"],
        ["edit_club", "edit_club.php", "03"]
    ];

} else {

    $workspace_links = [
        ["browse", "index.php"],
        ["registrations", "my_registrations.php"],
        ["explore", "explore_clubs.php"],
        ["followed", "my_clubs.php"],
        ["notifications", "notifications.php"]
    ];

    $dashboard_cards = [
        ["browse", "index.php", "01"],
        ["registrations", "my_registrations.php", "02"],
        ["explore", "explore_clubs.php", "03"],
        ["followed", "my_clubs.php", "04"],
        ["notifications", "notifications.php", "05"]
    ];
}

$account_links = [
    ["profile", "dashboard.php?section=profile"],
    ["language", "dashboard.php?section=language"],
    ["password", "dashboard.php?section=password"],
    ["help", "dashboard.php?section=help"]
];

$faqs = [
    "en" => [
        ["How can I change my name?",
         "Open My Profile, enter your new name, and save the changes."],
        ["How can I change my language?",
         "Open Language, select your preferred language, and save."],
        ["How can I reset my password?",
         "If you are logged in, use Change Password. Forgotten-password recovery is not available in this prototype."],
        ["How can I get help with an event?",
         "Open the event details or contact the club using its public contact email."]
    ],
    "ms" => [
        ["Bagaimana saya menukar nama?",
         "Buka Profil Saya, masukkan nama baharu dan simpan."],
        ["Bagaimana saya menukar bahasa?",
         "Buka Bahasa, pilih bahasa pilihan dan simpan."],
        ["Bagaimana saya menukar kata laluan?",
         "Gunakan Tukar Kata Laluan semasa log masuk. Pemulihan kata laluan terlupa belum tersedia."],
        ["Bagaimana mendapatkan bantuan acara?",
         "Lihat butiran acara atau hubungi kelab melalui e-mel awam."]
    ],
    "zh" => [
        ["如何修改姓名？",
         "打开个人资料，输入新姓名，然后保存。"],
        ["如何切换语言？",
         "打开语言设置，选择语言并保存。"],
        ["如何修改密码？",
         "登录后打开修改密码。当前原型暂不支持忘记密码找回。"],
        ["如何获得活动帮助？",
         "查看活动详情，或通过社团公开的联系邮箱咨询。"]
    ]
];

header("Cache-Control: no-store, private");
?>

<!DOCTYPE html>
<html lang="<?php echo h(
    $lang === "zh" ? "zh-CN" :
    ($lang === "ms" ? "ms" : "en")
); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo h(t("dashboard")); ?> - Agora</title>

    <link rel="stylesheet" href="style.css">

    <style>
        /* Sidebar Dashboard */

        .dash-shell {
            display: grid;
            grid-template-columns: 265px minmax(0, 1fr);
            min-height: 100vh;
        }

        .dash-sidebar {
            background: #fff;
            border-right: 1px solid #e5e7eb;
            padding: 30px 18px;
        }

        .dash-brand {
            display: block;
            padding: 0 14px;
            margin-bottom: 38px;
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -1.5px;
        }

        .dash-brand span {
            color: #2563eb;
        }

        .dash-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 13px;
            margin-bottom: 28px;
            min-width: 0;
        }

        .dash-avatar {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 20px;
        }

        .dash-user-details {
            min-width: 0;
        }

        .dash-user-name {
            font-size: 14px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .dash-user-role {
            font-size: 12px;
            color: #64748b;
        }

        .dash-nav-heading {
            padding: 8px 14px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.1px;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .dash-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 5px;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .dash-nav-link:hover {
            background: #f1f5f9;
        }

        .dash-nav-link.active {
            background: #eff6ff;
            color: #2563eb;
        }

        .dash-nav-symbol {
            width: 9px;
            height: 9px;
            border-radius: 3px;
            background: #cbd5e1;
            flex-shrink: 0;
        }

        .dash-nav-link.active .dash-nav-symbol {
            background: #2563eb;
        }

        .dash-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 20px 0;
        }

        .dash-main {
            padding: 54px clamp(22px, 5vw, 76px) 80px;
            min-width: 0;
        }

        .dash-eyebrow {
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
        }

        .dash-page-title {
            font-size: clamp(30px, 4vw, 44px);
            letter-spacing: -1.5px;
            margin: 14px 0;
            overflow-wrap: anywhere;
        }

        .dash-subtitle {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 42px;
        }

        .dash-section-title {
            font-size: 23px;
            margin-bottom: 22px;
        }

        .dash-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .dash-card {
            display: flex;
            flex-direction: column;
            min-height: 185px;
            padding: 25px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        .dash-card-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 800;
        }

        .dash-card h3 {
            font-size: 18px;
            margin: 18px 0 12px;
        }

        .dash-card .dash-card-open {
            margin-top: auto;
            color: #2563eb;
            font-weight: 700;
            font-size: 14px;
        }

        .dash-panel {
            max-width: 750px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 32px;
        }

        .dash-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 22px;
        }

        .dash-field label {
            font-size: 14px;
            font-weight: 700;
        }

        .dash-field input,
        .dash-field select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #dbe1ea;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font: inherit;
        }

        .dash-field input:focus,
        .dash-field select:focus {
            outline: 3px solid #dbeafe;
            border-color: #2563eb;
        }

        .dash-field input[readonly] {
            background: #f8fafc;
            color: #64748b;
        }

        .dash-btn {
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            padding: 13px 20px;
            cursor: pointer;
        }

        .dash-btn:hover {
            background: #1d4ed8;
        }

        .dash-alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .dash-alert-success {
            background: #ecfdf5;
            color: #047857;
        }

        .dash-alert-error {
            background: #fef2f2;
            color: #b91c1c;
        }

        .dash-note {
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .dash-faq {
            border-bottom: 1px solid #e5e7eb;
            padding: 14px 0;
        }

        .dash-faq:last-child {
            border-bottom: 0;
        }

        .dash-faq h3 {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .dash-faq p {
            font-size: 14px;
            color: #64748b;
        }

        .dash-footer {
            color: #94a3b8;
            margin-top: 65px;
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .dash-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 850px) {
            .dash-shell {
                grid-template-columns: 1fr;
            }

            .dash-sidebar {
                border-right: 0;
                border-bottom: 1px solid #e5e7eb;
                padding: 20px;
            }

            .dash-brand {
                margin-bottom: 16px;
            }

            .dash-user {
                max-width: 360px;
            }

            .dash-nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 3px 8px;
            }

            .dash-nav-heading,
            .dash-divider {
                grid-column: 1 / -1;
            }

            .dash-main {
                padding: 35px 20px 60px;
            }
        }

        @media (max-width: 520px) {
            .dash-cards,
            .dash-nav {
                grid-template-columns: 1fr;
            }

            .dash-panel {
                padding: 22px;
            }
        }
    </style>
</head>

<body>

<div class="dash-shell">

    <!-- Left Sidebar -->

    <aside class="dash-sidebar"
           aria-label="Account navigation">

        <a href="dashboard.php" class="dash-brand">
            agora<span>.</span>
        </a>

        <div class="dash-user">

            <div class="dash-avatar"
                 aria-hidden="true">👤</div>

            <div class="dash-user-details">

                <div class="dash-user-name">
                    <?php echo h($user["full_name"]); ?>
                </div>

                <div class="dash-user-role">
                    <?php echo h(
                        $is_organizer ? "Organizer" : "Student"
                    ); ?>
                </div>

            </div>

        </div>

        <nav class="dash-nav">

            <div class="dash-nav-heading">
                <?php echo h(t("workspace")); ?>
            </div>

            <a href="dashboard.php"
               class="dash-nav-link <?php
                   echo $section === "home" ? "active" : "";
               ?>"
               <?php if ($section === "home") { ?>
                   aria-current="page"
               <?php } ?>>

                <span class="dash-nav-symbol"></span>

                <?php echo h(t("dashboard")); ?>
            </a>

            <?php foreach ($workspace_links as $link) { ?>

                <a href="<?php echo h($link[1]); ?>"
                   class="dash-nav-link">

                    <span class="dash-nav-symbol"></span>

                    <?php echo h(t($link[0])); ?>

                </a>

            <?php } ?>

            <div class="dash-divider"></div>

            <div class="dash-nav-heading">
                <?php echo h(t("account")); ?>
            </div>

            <?php foreach ($account_links as $link) { ?>

                <?php
                $active = $section === $link[0];
                ?>

                <a href="<?php echo h($link[1]); ?>"
                   class="dash-nav-link <?php
                       echo $active ? "active" : "";
                   ?>"
                   <?php if ($active) { ?>
                       aria-current="page"
                   <?php } ?>>

                    <span class="dash-nav-symbol"></span>

                    <?php echo h(t($link[0])); ?>

                </a>

            <?php } ?>

            <a href="logout.php" class="dash-nav-link">
                <span class="dash-nav-symbol"></span>
                <?php echo h(t("logout")); ?>
            </a>

        </nav>

    </aside>

    <!-- Main Content -->

    <main class="dash-main">

        <?php if ($section === "home") { ?>

            <div class="dash-eyebrow">
                <?php echo h(t(
                    $is_organizer ? "organizer" : "student"
                )); ?>
            </div>

            <h1 class="dash-page-title">
                <?php echo h(t("welcome")); ?>,
                <?php echo h($user["full_name"]); ?>.
            </h1>

            <p class="dash-subtitle">
                <?php echo h(t(
                    $is_organizer
                        ? "organizer_desc"
                        : "student_desc"
                )); ?>
            </p>

            <h2 class="dash-section-title">
                <?php echo h(t("your_workspace")); ?>
            </h2>

            <div class="dash-cards">

                <?php foreach ($dashboard_cards as $card) { ?>

                    <a href="<?php echo h($card[1]); ?>"
                       class="dash-card">

                        <span class="dash-card-number">
                            <?php echo h($card[2]); ?>
                        </span>

                        <h3>
                            <?php echo h(t($card[0])); ?>
                        </h3>

                        <span class="dash-card-open">
                            <?php echo h(t("open")); ?> →
                        </span>

                    </a>

                <?php } ?>

            </div>

        <?php } elseif ($section === "profile") { ?>

            <div class="dash-eyebrow">
                <?php echo h(t("account")); ?>
            </div>

            <h1 class="dash-page-title">
                <?php echo h(t("profile_title")); ?>
            </h1>

            <p class="dash-subtitle">
                <?php echo h(t("profile_desc")); ?>
            </p>

            <div class="dash-panel">

                <?php if ($flash !== "") { ?>
                    <div class="dash-alert dash-alert-success"
                         role="status">
                        <?php echo h($flash); ?>
                    </div>
                <?php } ?>

                <?php if ($error !== "") { ?>
                    <div class="dash-alert dash-alert-error"
                         role="alert">
                        <?php echo h($error); ?>
                    </div>
                <?php } ?>

                <form action="dashboard.php?section=profile"
                      method="POST">

                    <input type="hidden"
                           name="token"
                           value="<?php echo h(
                               $_SESSION["dashboard_token"]
                           ); ?>">

                    <input type="hidden"
                           name="action"
                           value="update_profile">

                    <div class="dash-field">

                        <label for="full_name">
                            <?php echo h(t("full_name")); ?>
                        </label>

                        <input type="text"
                               id="full_name"
                               name="full_name"
                               maxlength="100"
                               value="<?php echo h(
                                   $_POST["full_name"] ??
                                   $user["full_name"]
                               ); ?>"
                               autocomplete="name"
                               required>
                    </div>

                    <div class="dash-field">

                        <label for="email">
                            <?php echo h(t("email")); ?>
                        </label>

                        <input type="email"
                               id="email"
                               value="<?php echo h(
                                   $user["email"]
                               ); ?>"
                               readonly>
                    </div>

                    <div class="dash-field">

                        <label for="role">
                            <?php echo h(t("role")); ?>
                        </label>

                        <input type="text"
                               id="role"
                               value="<?php echo h(
                                   $user["role"]
                               ); ?>"
                               readonly>
                    </div>

                    <?php if ($is_organizer) { ?>

                        <div class="dash-field">

                            <label for="club_name">
                                <?php echo h(t("club_name")); ?>
                            </label>

                            <input type="text"
                                   id="club_name"
                                   value="<?php echo h(
                                       $user["club_name"]
                                   ); ?>"
                                   readonly>
                        </div>

                    <?php } ?>

                    <p class="dash-note">
                        <?php echo h(t("profile_note")); ?>
                    </p>

                    <button class="dash-btn" type="submit">
                        <?php echo h(t("save_profile")); ?>
                    </button>

                </form>

            </div>

        <?php } elseif ($section === "language") { ?>

            <div class="dash-eyebrow">
                <?php echo h(t("account")); ?>
            </div>

            <h1 class="dash-page-title">
                <?php echo h(t("language_title")); ?>
            </h1>

            <p class="dash-subtitle">
                <?php echo h(t("language_desc")); ?>
            </p>

            <div class="dash-panel">

                <?php if ($error !== "") { ?>
                    <div class="dash-alert dash-alert-error"
                         role="alert">
                        <?php echo h($error); ?>
                    </div>
                <?php } ?>

                <form action="dashboard.php?section=language"
                      method="POST">

                    <input type="hidden"
                           name="token"
                           value="<?php echo h(
                               $_SESSION["dashboard_token"]
                           ); ?>">

                    <input type="hidden"
                           name="action"
                           value="update_language">

                    <div class="dash-field">

                        <label for="language">
                            <?php echo h(t("select_language")); ?>
                        </label>

                        <select id="language" name="language">

                            <option value="en"
                                <?php echo $lang === "en"
                                    ? "selected" : ""; ?>>
                                English
                            </option>

                            <option value="ms"
                                <?php echo $lang === "ms"
                                    ? "selected" : ""; ?>>
                                Bahasa Melayu
                            </option>

                            <option value="zh"
                                <?php echo $lang === "zh"
                                    ? "selected" : ""; ?>>
                                简体中文
                            </option>

                        </select>

                    </div>

                    <button class="dash-btn" type="submit">
                        <?php echo h(t("save_language")); ?>
                    </button>

                </form>

                <p class="dash-note">
                    <?php echo h(t("language_note")); ?>
                </p>

            </div>

        <?php } elseif ($section === "password") { ?>

            <div class="dash-eyebrow">
                <?php echo h(t("account")); ?>
            </div>

            <h1 class="dash-page-title">
                <?php echo h(t("password_title")); ?>
            </h1>

            <p class="dash-subtitle">
                <?php echo h(t("password_desc")); ?>
            </p>

            <div class="dash-panel">

                <?php if ($flash !== "") { ?>
                    <div class="dash-alert dash-alert-success"
                         role="status">
                        <?php echo h($flash); ?>
                    </div>
                <?php } ?>

                <?php if ($error !== "") { ?>
                    <div class="dash-alert dash-alert-error"
                         role="alert">
                        <?php echo h($error); ?>
                    </div>
                <?php } ?>

                <form action="dashboard.php?section=password"
                      method="POST">

                    <input type="hidden"
                           name="token"
                           value="<?php echo h(
                               $_SESSION["dashboard_token"]
                           ); ?>">

                    <input type="hidden"
                           name="action"
                           value="update_password">

                    <div class="dash-field">
                        <label for="current_password">
                            <?php echo h(
                                t("current_password")
                            ); ?>
                        </label>

                        <input type="password"
                               id="current_password"
                               name="current_password"
                               autocomplete="current-password"
                               required>
                    </div>

                    <div class="dash-field">
                        <label for="new_password">
                            <?php echo h(t("new_password")); ?>
                        </label>

                        <input type="password"
                               id="new_password"
                               name="new_password"
                               autocomplete="new-password"
                               minlength="8"
                               required>
                    </div>

                    <div class="dash-field">
                        <label for="confirm_password">
                            <?php echo h(
                                t("confirm_password")
                            ); ?>
                        </label>

                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               autocomplete="new-password"
                               minlength="8"
                               required>
                    </div>

                    <p class="dash-note">
                        <?php echo h(t("password_note")); ?>
                    </p>

                    <button class="dash-btn" type="submit">
                        <?php echo h(t("save_password")); ?>
                    </button>

                </form>

            </div>

        <?php } elseif ($section === "help") { ?>

            <div class="dash-eyebrow">
                <?php echo h(t("account")); ?>
            </div>

            <h1 class="dash-page-title">
                <?php echo h(t("help_title")); ?>
            </h1>

            <p class="dash-subtitle">
                <?php echo h(t("help_desc")); ?>
            </p>

            <div class="dash-panel">

                <?php foreach ($faqs[$lang] as $faq) { ?>

                    <div class="dash-faq">

                        <h3>
                            <?php echo h($faq[0]); ?>
                        </h3>

                        <p>
                            <?php echo h($faq[1]); ?>
                        </p>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

        <footer class="dash-footer">
            Agora — Taylor's University Event Platform
        </footer>

    </main>

</div>

</body>
</html>