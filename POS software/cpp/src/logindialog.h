#pragma once

#include <QDialog>
#include <QLineEdit>
#include <QLabel>
#include <QPushButton>
#include <QVBoxLayout>
#include <QVariantMap>

class LoginDialog : public QDialog {
    Q_OBJECT
public:
    explicit LoginDialog(QWidget* parent = nullptr);

    QString loggedInUser() const { return m_user; }
    QString loggedInRole() const { return m_role; }
    int     loggedInId()   const { return m_userId; }

private slots:
    void doLogin();

private:
    QLineEdit* m_username;
    QLineEdit* m_password;
    QLabel*    m_errorLabel;
    QPushButton* m_loginBtn;

    QString m_user;
    QString m_role;
    int     m_userId = 0;
};
