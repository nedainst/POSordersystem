#include "logindialog.h"
#include "database.h"
#include "styles.h"
#include <QKeyEvent>
#include <QGraphicsDropShadowEffect>
#include <QApplication>
#include <QScreen>

LoginDialog::LoginDialog(QWidget* parent)
    : QDialog(parent)
{
    setWindowTitle("POS System - Login");
    setFixedSize(420, 520);
    setWindowFlags(Qt::FramelessWindowHint | Qt::Dialog);
    setAttribute(Qt::WA_TranslucentBackground);
    setStyleSheet(Style::appStyleSheet());

    // ── Outer layout ─────────────────────────────────────────
    auto* outer = new QVBoxLayout(this);
    outer->setContentsMargins(20, 20, 20, 20);

    auto* card = new QFrame;
    card->setStyleSheet(QStringLiteral(
        "QFrame { background-color: %1; border-radius: 20px; border: 1px solid %2; }")
        .arg(Style::BG_CARD, Style::BORDER));

    auto shadow = new QGraphicsDropShadowEffect;
    shadow->setBlurRadius(40);
    shadow->setOffset(0, 8);
    shadow->setColor(QColor(0, 0, 0, 100));
    card->setGraphicsEffect(shadow);

    auto* mainLay = new QVBoxLayout(card);
    mainLay->setContentsMargins(40, 40, 40, 40);
    mainLay->setSpacing(8);

    // ── Icon / logo area ─────────────────────────────────────
    auto* icon = new QLabel(QStringLiteral("\xF0\x9F\x9B\x92")); // 🛒
    icon->setAlignment(Qt::AlignCenter);
    icon->setStyleSheet("font-size: 48px; padding: 10px;");
    mainLay->addWidget(icon);

    auto* title = new QLabel("POS System");
    title->setAlignment(Qt::AlignCenter);
    title->setStyleSheet(QStringLiteral(
        "font-size: 24px; font-weight: 700; color: %1; margin-bottom: 4px;")
        .arg(Style::TEXT_PRIMARY));
    mainLay->addWidget(title);

    auto* subtitle = new QLabel("Silakan masuk untuk melanjutkan");
    subtitle->setAlignment(Qt::AlignCenter);
    subtitle->setStyleSheet(QStringLiteral(
        "font-size: 12px; color: %1; margin-bottom: 16px;").arg(Style::TEXT_MUTED));
    mainLay->addWidget(subtitle);

    // ── Username ─────────────────────────────────────────────
    auto* userLabel = new QLabel("Username");
    userLabel->setStyleSheet(QStringLiteral(
        "font-size: 12px; font-weight: 600; color: %1; margin-top: 8px;").arg(Style::TEXT_SECONDARY));
    mainLay->addWidget(userLabel);

    m_username = new QLineEdit;
    m_username->setPlaceholderText("Masukkan username");
    m_username->setMinimumHeight(42);
    mainLay->addWidget(m_username);

    // ── Password ─────────────────────────────────────────────
    auto* passLabel = new QLabel("Password");
    passLabel->setStyleSheet(QStringLiteral(
        "font-size: 12px; font-weight: 600; color: %1; margin-top: 8px;").arg(Style::TEXT_SECONDARY));
    mainLay->addWidget(passLabel);

    m_password = new QLineEdit;
    m_password->setPlaceholderText("Masukkan password");
    m_password->setEchoMode(QLineEdit::Password);
    m_password->setMinimumHeight(42);
    mainLay->addWidget(m_password);

    // ── Error label ──────────────────────────────────────────
    m_errorLabel = new QLabel;
    m_errorLabel->setAlignment(Qt::AlignCenter);
    m_errorLabel->setStyleSheet(QStringLiteral(
        "color: %1; font-size: 12px; margin-top: 4px;").arg(Style::DANGER));
    m_errorLabel->hide();
    mainLay->addWidget(m_errorLabel);

    mainLay->addSpacing(8);

    // ── Login button ─────────────────────────────────────────
    m_loginBtn = new QPushButton("Masuk");
    m_loginBtn->setMinimumHeight(44);
    m_loginBtn->setCursor(Qt::PointingHandCursor);
    m_loginBtn->setStyleSheet(QStringLiteral(
        "QPushButton { background-color: %1; color: white; border-radius: 12px;"
        " font-size: 15px; font-weight: 700; }"
        "QPushButton:hover { background-color: %2; }"
        "QPushButton:pressed { background-color: #5a52dd; }")
        .arg(Style::ACCENT, Style::ACCENT_HOVER));
    mainLay->addWidget(m_loginBtn);

    mainLay->addSpacing(12);

    auto* hint = new QLabel("Demo: admin / admin123");
    hint->setAlignment(Qt::AlignCenter);
    hint->setStyleSheet(QStringLiteral(
        "font-size: 11px; color: %1;").arg(Style::TEXT_MUTED));
    mainLay->addWidget(hint);

    outer->addWidget(card);

    // ── Connections ──────────────────────────────────────────
    connect(m_loginBtn, &QPushButton::clicked, this, &LoginDialog::doLogin);
    connect(m_password, &QLineEdit::returnPressed, this, &LoginDialog::doLogin);
    connect(m_username, &QLineEdit::returnPressed, [this]{ m_password->setFocus(); });

    // center on screen
    if (auto* scr = QApplication::primaryScreen()) {
        auto r = scr->availableGeometry();
        move(r.center() - rect().center());
    }
}

void LoginDialog::doLogin() {
    QString user = m_username->text().trimmed();
    QString pass = m_password->text();
    if (user.isEmpty() || pass.isEmpty()) {
        m_errorLabel->setText("Username dan password harus diisi");
        m_errorLabel->show();
        return;
    }

    auto result = Database::instance().authenticate(user, pass);
    if (result.isEmpty()) {
        m_errorLabel->setText("Username atau password salah!");
        m_errorLabel->show();
        m_password->clear();
        m_password->setFocus();
        return;
    }

    m_user   = result["full_name"].toString();
    m_role   = result["role"].toString();
    m_userId = result["id"].toInt();
    accept();
}
