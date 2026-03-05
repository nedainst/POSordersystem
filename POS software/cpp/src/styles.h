#pragma once
#include <QString>

namespace Style {

// ── Color Palette ────────────────────────────────────────────
inline constexpr auto BG_DARK        = "#0f0f14";
inline constexpr auto BG_SIDEBAR     = "#16161d";
inline constexpr auto BG_CARD        = "#1e1e2e";
inline constexpr auto BG_CARD_HOVER  = "#262638";
inline constexpr auto BG_ENTRY       = "#252535";
inline constexpr auto ACCENT         = "#6c63ff";
inline constexpr auto ACCENT_HOVER   = "#7b73ff";
inline constexpr auto ACCENT_LIGHT   = "#8a82ff";
inline constexpr auto SUCCESS        = "#2ecc71";
inline constexpr auto SUCCESS_HOVER  = "#27ae60";
inline constexpr auto WARNING        = "#f39c12";
inline constexpr auto DANGER         = "#e74c3c";
inline constexpr auto DANGER_HOVER   = "#c0392b";
inline constexpr auto TEXT_PRIMARY   = "#e8e8f0";
inline constexpr auto TEXT_SECONDARY = "#a9a9c0";
inline constexpr auto TEXT_MUTED     = "#6c6c88";
inline constexpr auto BORDER         = "#2a2a3d";
inline constexpr auto HEADER_BG      = "#1a1a28";
inline constexpr auto TABLE_ALT      = "#1b1b2b";
inline constexpr auto SCROLLBAR      = "#3a3a50";

// ── Global Application Stylesheet ────────────────────────────
inline QString appStyleSheet() {
    return QStringLiteral(R"(
        /* ── Global ─────────────────────────────────────── */
        QWidget {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            color: #e8e8f0;
        }
        QMainWindow, QDialog {
            background-color: #0f0f14;
        }

        /* ── Scrollbar ──────────────────────────────────── */
        QScrollBar:vertical {
            background: #16161d;
            width: 10px;
            margin: 0;
            border-radius: 5px;
        }
        QScrollBar::handle:vertical {
            background: #3a3a50;
            border-radius: 5px;
            min-height: 30px;
        }
        QScrollBar::handle:vertical:hover {
            background: #6c63ff;
        }
        QScrollBar::add-line:vertical, QScrollBar::sub-line:vertical,
        QScrollBar::add-page:vertical, QScrollBar::sub-page:vertical {
            background: none; height: 0;
        }
        QScrollBar:horizontal {
            background: #16161d;
            height: 10px;
            border-radius: 5px;
        }
        QScrollBar::handle:horizontal {
            background: #3a3a50;
            border-radius: 5px;
            min-width: 30px;
        }
        QScrollBar::handle:horizontal:hover {
            background: #6c63ff;
        }
        QScrollBar::add-line:horizontal, QScrollBar::sub-line:horizontal,
        QScrollBar::add-page:horizontal, QScrollBar::sub-page:horizontal {
            background: none; width: 0;
        }

        /* ── QLineEdit ──────────────────────────────────── */
        QLineEdit {
            background-color: #252535;
            border: 1px solid #2a2a3d;
            border-radius: 10px;
            padding: 8px 14px;
            color: #e8e8f0;
            font-size: 13px;
            selection-background-color: #6c63ff;
        }
        QLineEdit:focus {
            border: 1px solid #6c63ff;
        }
        QLineEdit:disabled {
            color: #6c6c88;
            background-color: #1a1a28;
        }

        /* ── QPushButton ────────────────────────────────── */
        QPushButton {
            background-color: #6c63ff;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
        }
        QPushButton:hover {
            background-color: #7b73ff;
        }
        QPushButton:pressed {
            background-color: #5a52dd;
        }
        QPushButton:disabled {
            background-color: #3a3a50;
            color: #6c6c88;
        }

        /* ── QComboBox ──────────────────────────────────── */
        QComboBox {
            background-color: #252535;
            border: 1px solid #2a2a3d;
            border-radius: 10px;
            padding: 8px 14px;
            color: #e8e8f0;
            font-size: 13px;
            min-width: 120px;
        }
        QComboBox:focus, QComboBox:on {
            border: 1px solid #6c63ff;
        }
        QComboBox::drop-down {
            subcontrol-origin: padding;
            subcontrol-position: center right;
            width: 28px;
            border: none;
        }
        QComboBox::down-arrow {
            image: none;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid #a9a9c0;
            margin-right: 8px;
        }
        QComboBox QAbstractItemView {
            background-color: #1e1e2e;
            border: 1px solid #2a2a3d;
            border-radius: 8px;
            color: #e8e8f0;
            selection-background-color: #6c63ff;
            selection-color: white;
            padding: 4px;
        }

        /* ── QTableWidget ───────────────────────────────── */
        QTableWidget {
            background-color: #0f0f14;
            alternate-background-color: #1b1b2b;
            border: none;
            gridline-color: #2a2a3d;
            color: #e8e8f0;
            font-size: 12px;
            selection-background-color: #6c63ff44;
            selection-color: #e8e8f0;
        }
        QTableWidget::item {
            padding: 6px 10px;
            border-bottom: 1px solid #1e1e2e;
        }
        QTableWidget::item:selected {
            background-color: #6c63ff44;
        }
        QHeaderView::section {
            background-color: #1a1a28;
            color: #a9a9c0;
            padding: 8px 10px;
            border: none;
            border-bottom: 2px solid #2a2a3d;
            font-weight: 600;
            font-size: 11px;
        }
        QHeaderView::section:hover {
            background-color: #262638;
        }

        /* ── QTabWidget ─────────────────────────────────── */
        QTabWidget::pane {
            border: none;
            background-color: #0f0f14;
        }
        QTabBar::tab {
            background-color: #252535;
            color: #a9a9c0;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            margin: 2px 3px;
            font-size: 12px;
            font-weight: 500;
        }
        QTabBar::tab:selected {
            background-color: #6c63ff;
            color: white;
        }
        QTabBar::tab:hover:!selected {
            background-color: #262638;
        }

        /* ── QLabel ─────────────────────────────────────── */
        QLabel {
            color: #e8e8f0;
            background: transparent;
        }

        /* ── QCheckBox ──────────────────────────────────── */
        QCheckBox {
            color: #e8e8f0;
            font-size: 13px;
            spacing: 8px;
        }
        QCheckBox::indicator {
            width: 18px; height: 18px;
            border: 2px solid #2a2a3d;
            border-radius: 4px;
            background: #252535;
        }
        QCheckBox::indicator:checked {
            background: #6c63ff;
            border-color: #6c63ff;
        }

        /* ── QSpinBox ───────────────────────────────────── */
        QSpinBox, QDoubleSpinBox {
            background-color: #252535;
            border: 1px solid #2a2a3d;
            border-radius: 10px;
            padding: 8px 14px;
            color: #e8e8f0;
            font-size: 13px;
        }
        QSpinBox:focus, QDoubleSpinBox:focus {
            border: 1px solid #6c63ff;
        }
        QSpinBox::up-button, QSpinBox::down-button,
        QDoubleSpinBox::up-button, QDoubleSpinBox::down-button {
            background: #2a2a3d;
            border: none;
            width: 20px;
        }

        /* ── QProgressBar ───────────────────────────────── */
        QProgressBar {
            background-color: #252535;
            border: none;
            border-radius: 5px;
            height: 10px;
            text-align: center;
            color: transparent;
        }
        QProgressBar::chunk {
            background-color: #6c63ff;
            border-radius: 5px;
        }

        /* ── QGroupBox ──────────────────────────────────── */
        QGroupBox {
            background-color: #1e1e2e;
            border: 1px solid #2a2a3d;
            border-radius: 14px;
            margin-top: 16px;
            padding-top: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #e8e8f0;
        }
        QGroupBox::title {
            subcontrol-origin: margin;
            subcontrol-position: top left;
            padding: 4px 12px;
            color: #a9a9c0;
        }

        /* ── QToolTip ───────────────────────────────────── */
        QToolTip {
            background-color: #1e1e2e;
            color: #e8e8f0;
            border: 1px solid #2a2a3d;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 12px;
        }

        /* ── QMessageBox ────────────────────────────────── */
        QMessageBox {
            background-color: #1e1e2e;
        }
        QMessageBox QLabel {
            color: #e8e8f0;
            font-size: 13px;
        }
        QMessageBox QPushButton {
            min-width: 80px;
            padding: 8px 20px;
        }

        /* ── QMenu ──────────────────────────────────────── */
        QMenu {
            background-color: #1e1e2e;
            border: 1px solid #2a2a3d;
            border-radius: 8px;
            padding: 4px;
        }
        QMenu::item {
            padding: 8px 24px;
            border-radius: 4px;
            color: #e8e8f0;
        }
        QMenu::item:selected {
            background-color: #6c63ff;
        }
    )");
}

// ── Reusable component styles ────────────────────────────────

inline QString cardStyle() {
    return QStringLiteral(
        "background-color: %1; border-radius: 16px; border: none;"
    ).arg(BG_CARD);
}

inline QString headerStyle() {
    return QStringLiteral(
        "background-color: %1; border: none;"
    ).arg(HEADER_BG);
}

inline QString sidebarBtnStyle(bool active) {
    if (active) {
        return QStringLiteral(
            "QPushButton { background-color: %1; color: white; text-align: left; "
            "padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; }"
            "QPushButton:hover { background-color: %2; }"
        ).arg(ACCENT, ACCENT_HOVER);
    }
    return QStringLiteral(
        "QPushButton { background-color: transparent; color: %1; text-align: left; "
        "padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; }"
        "QPushButton:hover { background-color: %2; color: %3; }"
    ).arg(TEXT_SECONDARY, BG_CARD_HOVER, TEXT_PRIMARY);
}

inline QString dangerBtnStyle() {
    return QStringLiteral(
        "QPushButton { background-color: %1; color: white; border-radius: 10px; "
        "padding: 10px 20px; font-weight: 600; }"
        "QPushButton:hover { background-color: %2; }"
    ).arg(DANGER, DANGER_HOVER);
}

inline QString successBtnStyle() {
    return QStringLiteral(
        "QPushButton { background-color: %1; color: white; border-radius: 10px; "
        "padding: 10px 20px; font-weight: 600; }"
        "QPushButton:hover { background-color: %2; }"
    ).arg(SUCCESS, SUCCESS_HOVER);
}

inline QString flatBtnStyle() {
    return QStringLiteral(
        "QPushButton { background-color: %1; color: %2; border-radius: 10px; "
        "padding: 10px 20px; font-size: 13px; }"
        "QPushButton:hover { background-color: %3; color: %4; }"
    ).arg(BG_ENTRY, TEXT_SECONDARY, BG_CARD_HOVER, TEXT_PRIMARY);
}

inline QString accentBarStyle(const char* color) {
    return QStringLiteral(
        "background-color: %1; border-radius: 2px;"
    ).arg(color);
}

inline QString formatRupiah(double amount) {
    return QStringLiteral("Rp %L1").arg(qint64(amount));
}

} // namespace Style
