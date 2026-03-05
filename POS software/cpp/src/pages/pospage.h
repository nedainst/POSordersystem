#pragma once

#include <QWidget>
#include <QLineEdit>
#include <QLabel>
#include <QTableWidget>
#include <QComboBox>
#include <QPushButton>
#include <QGridLayout>
#include <QVBoxLayout>
#include <QVariantMap>
#include <QVector>

class POSPage : public QWidget {
    Q_OBJECT
public:
    explicit POSPage(int cashierId, QWidget* parent = nullptr);

private slots:
    void onCategoryChanged();
    void onSearch();
    void addToCart(int productId);
    void updateCartUI();
    void removeFromCart(int row);
    void changeQty(int row, int delta);
    void processPayment();
    void clearCart();

private:
    void buildUI();
    void loadProducts();
    void loadCategories();
    void updateTotals();
    void showReceipt(const QVariantMap& tx);

    // Left panel – products
    QLineEdit*   m_searchEdit;
    QComboBox*   m_categoryCombo;
    QGridLayout* m_productGrid;
    QWidget*     m_productContainer;

    // Right panel – cart
    QTableWidget* m_cartTable;
    QLineEdit*    m_customerEdit;
    QLineEdit*    m_discountEdit;
    QComboBox*    m_paymentCombo;
    QLineEdit*    m_paymentEdit;
    QLabel*       m_subtotalLabel;
    QLabel*       m_taxLabel;
    QLabel*       m_discountLabel;
    QLabel*       m_totalLabel;
    QLabel*       m_changeLabel;

    // cart data
    struct CartItem {
        int     productId;
        QString name;
        double  price;
        int     qty;
        int     maxStock;
    };
    QVector<CartItem> m_cart;
    double m_taxRate = 11.0;
    int    m_cashierId;
};
