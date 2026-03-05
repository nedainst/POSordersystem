#pragma once
#include <QWidget>
#include <QLineEdit>
#include <QLabel>

class SettingsPage : public QWidget {
    Q_OBJECT
public:
    explicit SettingsPage(QWidget* parent = nullptr);
private slots:
    void saveSettings();
    void resetDatabase();
private:
    void buildUI();
    void loadSettings();
    QLineEdit* m_storeName;
    QLineEdit* m_storeAddress;
    QLineEdit* m_storePhone;
    QLineEdit* m_taxRate;
    QLineEdit* m_receiptFooter;
    QLineEdit* m_lowStockThreshold;
};
