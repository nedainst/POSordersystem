#pragma once

#include <QMainWindow>
#include <QStackedWidget>
#include <QPushButton>
#include <QLabel>
#include <QVBoxLayout>
#include <QFrame>
#include <QVector>

class MainWindow : public QMainWindow {
    Q_OBJECT
public:
    explicit MainWindow(const QString& userName, const QString& role,
                        int userId, QWidget* parent = nullptr);

private slots:
    void navigateTo(int index);
    void onLogout();

private:
    void buildSidebar();
    void buildPages();
    void updateSidebarButtons();

    QFrame*         m_sidebar;
    QStackedWidget* m_stack;
    QLabel*         m_userLabel;
    QLabel*         m_roleLabel;

    struct NavItem { QString icon; QString text; };
    QVector<NavItem>      m_navItems;
    QVector<QPushButton*> m_navButtons;
    int m_currentIndex = 0;

    QString m_userName;
    QString m_userRole;
    int     m_userId;
};
