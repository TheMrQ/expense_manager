const GOAL_ICON_COLORS = ['#3498db', '#e74c3c', '#f1c40f', '#2ecc71', '#9b59b6', '#e67e22'];
let myTopExpensesChart = null;
let myBalanceExpenseChart = null;
// === Sidebar Active Link and Section Management ===
document.addEventListener('DOMContentLoaded', () => {
    initializeCurrencyConverter();
    loadUserDetails();
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const contentSections = document.querySelectorAll('.content-section');
    const addTransactionButton = document.querySelector('.add-transaction-btn');
    const generateBtn = document.getElementById('generateReportBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', generateReport);
    }
    
    const downloadBtn = document.getElementById('downloadPdfBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', downloadReportAsPDF);
    }

    if (calendarContainer) {
        calendarContainer.addEventListener('click', function(event) {
            const targetCell = event.target.closest('.datepicker-cell.has-transaction');
            if (targetCell && targetCell.dataset.transactionId) {
                const transactionId = targetCell.dataset.transactionId;
                
                setActiveLinkAndSection('transactions'); // Switch to transactions page

                setTimeout(() => {
                    scrollToTransaction(transactionId); // Scroll to the transaction
                }, 500);
            }
        });
    }

    function setActiveLinkAndSection(targetSectionId) {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const contentSections = document.querySelectorAll('.content-section');
    const addTransactionButton = document.querySelector('.add-transaction-btn');
    const dashboardHeader = document.querySelector('.dashboard-header h1');
    


    
    // --- 1. Update Header Text ---
    if (targetSectionId === 'overview') {
        dashboardHeader.innerHTML = 'Welcome <span id="username">...</span> 👋';
        fetch('../api/user/getUsername.php')
            .then(response => response.text())
            .then(name => {
                const userSpan = document.getElementById('username');
                if (userSpan) userSpan.textContent = name;
            });
    } else {
        const activeLink = document.querySelector(`.sidebar-link[data-section="${targetSectionId}"]`);
        dashboardHeader.textContent = activeLink.textContent;
    }

    // --- 2. Re-trigger CSS Animation ---
    dashboardHeader.style.animation = 'none';
    void dashboardHeader.offsetWidth; // Force repaint
    dashboardHeader.style.animation = '';

    // --- 3. Update Active Classes and Button Visibility ---
    sidebarLinks.forEach(link => link.classList.remove('active'));
    contentSections.forEach(section => section.classList.remove('active-section'));

    const activeLinkForClass = document.querySelector(`.sidebar-link[data-section="${targetSectionId}"]`);
    if (activeLinkForClass) activeLinkForClass.classList.add('active');

    const activeSection = document.getElementById(`${targetSectionId}-section`);
    if (activeSection) activeSection.classList.add('active-section');

    if (addTransactionButton) {
        if (targetSectionId === 'transactions') {
            addTransactionButton.style.display = 'inline-flex';
        } else {
            addTransactionButton.style.display = 'none';
        }
    }

    // --- 4. Call Section-Specific Functions ---

    if (targetSectionId === 'bills') {
        fetchBills();
    }

    if (targetSectionId === 'transactions') {
        fetchTransactions();
    } else if (targetSectionId === 'overview') {
        loadOverviewStats();
        const currentMonth = new Date().toISOString().slice(0, 7);
        fetchAndHighlightTransactions(currentMonth);

        // CORRECTED: This now targets the correct element ID from your HTML
        populateRecentTransactions(4, 'overviewRecentActivityList');

        fetch('../api/user/get_top_expenses.php')
            .then(res => res.json())
            .then(result => {
                if (result.success) createTopExpensesChart(result.data);
            });
    } else if (targetSectionId === 'balances') {
        fetchAndDisplayBalances();
    } else if (targetSectionId === 'expenses') {
        loadExpenseCharts();
    }


    if (targetSectionId === 'goals') {
        fetchGoals();
    }

    if (targetSectionId === 'budgets') {
        fetchBudgets();
    }

}

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            setActiveLinkAndSection(this.dataset.section);
        });
    });

    // Initial load
    setActiveLinkAndSection('overview');

    // --- Logic for collapsible accordion sub-menus ---
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(clickedToggle => {
        clickedToggle.addEventListener('click', function(event) {
            event.preventDefault();

            // Close all other sub-menus
            submenuToggles.forEach(otherToggle => {
                if (otherToggle !== clickedToggle) {
                    otherToggle.classList.remove('open');
                    otherToggle.nextElementSibling.classList.remove('open');
                }
            });

            // Toggle the clicked sub-menu
            this.classList.toggle('open');
            this.nextElementSibling.classList.toggle('open');
        });
    });

    

});

// === Fetch & Set Username ===
fetch('../api/user/getUsername.php')
    .then(response => response.text())
    .then(name => document.getElementById('username').textContent = name)
    .catch(err => {
        console.error('Failed to load username:', err);
        document.getElementById('username').textContent = 'User';
    });

// === Modal and Form Elements ===
const transactionModal = document.getElementById('transactionModal');
const transactionForm = document.getElementById('transactionForm');
const modalTitle = document.getElementById('modalTitle');
const transactionIdInput = document.getElementById('transactionId');
const messageBox = document.getElementById('messageBox');
const confirmationModal = document.getElementById('confirmationModal');
const confirmOkBtn = document.getElementById('confirmOkBtn');
const confirmCancelBtn = document.getElementById('confirmCancelBtn');

// === Modal Control Functions ===


let displayCurrency = 'USD';
const currencySymbols = { USD: '$', VND: '₫', EUR: '€' };

const staticExchangeRates = {
    "USD": 1.0,
    "VND": 25455.0,
    "EUR": 0.93
};

/**
 * Converts an amount using the static exchange rates.
 */
function convertAndFormat(amount, fromCurrency) {
    if (!staticExchangeRates[fromCurrency] || !staticExchangeRates[displayCurrency]) {
        const symbol = currencySymbols[fromCurrency] || fromCurrency;
        return `${new Intl.NumberFormat().format(amount.toFixed(2))} ${symbol}`;
    }

    const amountInUSD = amount / staticExchangeRates[fromCurrency];
    const convertedAmount = amountInUSD * staticExchangeRates[displayCurrency];

    const symbol = currencySymbols[displayCurrency] || displayCurrency;
    const formattedAmount = new Intl.NumberFormat(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(convertedAmount);

    return `${symbol}${formattedAmount}`;
}

/**
 * Initializes the currency converter with the static rates.
 */
function initializeCurrencyConverter() {
    const selector = document.getElementById('currencySelector');
    const customTrigger = document.querySelector('.custom-select-trigger');
    const optionsContainer = document.getElementById('customOptionsContainer');
    const selectedCurrencySpan = document.getElementById('selectedCurrency');

    // Populate the hidden select and the custom options list
    for (const currencyCode in staticExchangeRates) {
        const originalOption = document.createElement('option');
        originalOption.value = currencyCode;
        originalOption.textContent = currencyCode;
        if (currencyCode === 'USD') originalOption.selected = true;
        selector.appendChild(originalOption);

        const customOption = document.createElement('div');
        customOption.className = 'custom-option';
        customOption.dataset.value = currencyCode;
        customOption.textContent = currencyCode;
        if (currencyCode === 'USD') customOption.classList.add('selected');
        optionsContainer.appendChild(customOption);
    }

    // Toggle dropdown visibility
    customTrigger.addEventListener('click', () => {
        optionsContainer.classList.toggle('open');
    });

    // Handle option selection
    optionsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('custom-option')) {
            const selectedValue = e.target.dataset.value;
            
            selectedCurrencySpan.textContent = selectedValue;
            optionsContainer.querySelectorAll('.custom-option').forEach(opt => opt.classList.remove('selected'));
            e.target.classList.add('selected');
            
            selector.value = selectedValue;
            displayCurrency = selectedValue;

            // Refresh the dashboard
            const activeSectionId = document.querySelector('.content-section.active-section')?.id.replace('-section', '');
            if (activeSectionId) {
                if (activeSectionId === 'overview') loadOverviewStats();
                if (activeSectionId === 'balances') fetchAndDisplayBalances();
                if (activeSectionId === 'transactions') fetchTransactions();
                if (activeSectionId === 'expenses') loadExpenseCharts();
                if (activeSectionId === 'goals') fetchGoals(); 
            }

            optionsContainer.classList.remove('open');
        }
    });

    // Close dropdown if clicked outside
    window.addEventListener('click', (e) => {
        if (!customTrigger.contains(e.target)) {
            optionsContainer.classList.remove('open');
        }
    });
}


function openTransactionModal(transactionData = null) {
    transactionForm.reset();
    messageBox.classList.add('hidden');
    messageBox.textContent = '';

    if (transactionData) {
        modalTitle.textContent = 'Edit Transaction';
        transactionIdInput.value = transactionData.id;
        document.getElementById('currency').value = transactionData.currency;
        document.getElementById('amount').value = transactionData.amount;
        document.getElementById('date').value = transactionData.date;
        document.getElementById('note').value = transactionData.note;
        
        fetchCategories().then(() => {
            document.getElementById('category').value = transactionData.category_id;
        });
    } else {
        modalTitle.textContent = 'Add Transaction';
        transactionIdInput.value = '';
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        document.getElementById('date').value = `${year}-${month}-${day}`;
        fetchCategories();
    }
    transactionModal.style.display = 'flex';
}

function closeTransactionModal() {
    transactionModal.style.display = 'none';
}

// === Confirmation Modal Logic ===
let confirmCallback = null;

function showConfirmationModal(callback) {
    confirmationModal.style.display = 'flex';
    confirmCallback = callback;
}

confirmOkBtn.addEventListener('click', () => {
    if (confirmCallback) confirmCallback();
    confirmationModal.style.display = 'none';
    confirmCallback = null;
});

confirmCancelBtn.addEventListener('click', () => {
    confirmationModal.style.display = 'none';
    confirmCallback = null;
});

// === Data Fetching and Handling ===
function showMessage(message, type = 'success') {
    messageBox.textContent = message;
    messageBox.className = 'message-box';
    if (type === 'success') {
        messageBox.classList.add('bg-green-100', 'text-green-800');
    } else {
        messageBox.classList.add('bg-red-100', 'text-red-800');
    }
    setTimeout(() => messageBox.classList.add('hidden'), 5000);
}

function fetchCategories() {
    return fetch('../api/user/get_categories.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const categorySelect = document.getElementById('category');
                categorySelect.innerHTML = '<option value="">Select Category</option>';
                result.data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = `${category.name} (${category.type})`;
                    categorySelect.appendChild(option);
                });
            } else {
                console.error('Failed to fetch categories:', result.error);
            }
        });
}

function refreshDataForActiveSection() {
    const activeSectionElement = document.querySelector('.content-section.active-section');
    if (!activeSectionElement) return;

    if (activeSectionElement.id === 'transactions-section') {
        fetchTransactions();
    }
    // Check if calendar exists before trying to access it
    if (calendar) {
        const currentMonth = new Date(calendar.getDate()).toISOString().slice(0, 7);
        fetchAndHighlightTransactions(currentMonth);
    }
}

// === Form Submission (Add & Update) ===
transactionForm.addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const transactionId = formData.get('transaction_id');
    const url = transactionId ? '../api/user/update_transaction.php' : '../api/user/add_transaction.php';
    
    fetch(url, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeTransactionModal();
            // **THE FIX:** Refresh the data here, only after a successful save.
            refreshDataForActiveSection(); 
        } else {
            showMessage(result.error, 'error');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showMessage('An unexpected error occurred.', 'error');
    });
});


// === Color and Hashing Utilities ===
const CATEGORY_COLORS = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#d946ef', '#ec4899'];

function simpleHash(str) {
    let hash = 0;
    if (!str) return hash;
    for (let i = 0; i < str.length; i++) {
        hash = ((hash << 5) - hash) + str.charCodeAt(i);
        hash |= 0;
    }
    return Math.abs(hash);
}

function getHexColorForCategory(categoryName) {
    // Special case for 'Salary'
    if (categoryName === 'Salary') {
        return '#22c55e'; // A vibrant green
    }
    
    // Fallback to the automatic hashing for all other categories
    const hash = simpleHash(categoryName);
    return CATEGORY_COLORS[hash % CATEGORY_COLORS.length];
}

function getCategoryColorClass(categoryName) {
    // Special case for 'Salary'
    if (categoryName === 'Salary') {
        return 'category-color-4'; // The class corresponding to the green color
    }

    // Fallback to the automatic hashing
    const hash = simpleHash(categoryName);
    const colorIndex = hash % CATEGORY_COLORS.length;
    return `category-color-${colorIndex}`;
}
// === Calendar Logic ===
const calendarContainer = document.getElementById('calendarInline');
let calendar; 

function fetchAndHighlightTransactions(month) {
    fetch(`../api/user/get_user_transactions_by_month.php?month=${month}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                markTransactionDates(result.data);
            } else {
                console.error('Failed to fetch calendar highlights:', result.error);
            }
        });
}

function markTransactionDates(data) {
    const allCells = calendarContainer.querySelectorAll('.datepicker-cell');
    allCells.forEach(cell => {
        cell.classList.remove('has-transaction');
        cell.style.removeProperty('--dot-color');
        cell.removeAttribute('data-transaction-id'); // Clear old data
    });

    Object.keys(data).forEach(dateStr => {
        const transactionInfo = data[dateStr];
        const hexColor = getHexColorForCategory(transactionInfo.category_name);
        const targetTimestamp = new Date(dateStr + 'T00:00:00').getTime();
        const cell = calendarContainer.querySelector(`.datepicker-cell[data-date="${targetTimestamp}"]`);

        if (cell) {
            cell.classList.add('has-transaction');
            cell.style.setProperty('--dot-color', hexColor);
            // Store the transaction ID on the cell
            cell.dataset.transactionId = transactionInfo.transaction_id;
        }
    });
}


if (calendarContainer && !calendarContainer.classList.contains('datepicker-initialized')) {
    calendarContainer.classList.add('datepicker-initialized');
    
    calendar = new Datepicker(calendarContainer, {
        autohide: false,
        todayHighlight: true,
        inline: true
    });

    calendarContainer.addEventListener('changeMonth', (e) => {
        const newMonth = new Date(e.detail.date).toISOString().slice(0, 7);
        setTimeout(() => {
            fetchAndHighlightTransactions(newMonth);
        }, 100);
    });
}

// === Transaction List Logic ===
// === Transaction List Logic ===
// === Transaction List Logic ===
const transactionsContainer = document.getElementById('transactionsContainer');
const recentTransactionsList = document.getElementById('recentTransactionsList');
const monthlyTransactionsContainer = document.getElementById('monthlyTransactionsContainer');

/**
 * Helper function to create the HTML for a single transaction item.
 * This avoids code repetition.
 * @param {object} transaction - The transaction data object.
 * @returns {HTMLLIElement} - The list item element.
 */
// Helper object to convert currency codes to symbols

function createTransactionListItem(transaction) {
    const listItem = document.createElement('li');
    listItem.classList.add('transaction-item');
    listItem.dataset.id = transaction.id;

    const amountClass = transaction.category_type === 'income' ? 'income' : 'expense';
    const sign = transaction.category_type === 'expense' ? '-' : '+';
    
    // THE FIX: This ensures every transaction item is converted
    const formattedAmount = convertAndFormat(parseFloat(transaction.amount), transaction.currency);

    const iconClass = getIconForCategory(transaction.category_name);
    const colorClass = getCategoryColorClass(transaction.category_name);

    listItem.innerHTML = `
        <div class="transaction-icon ${colorClass}">
            <i class="${iconClass}"></i>
        </div>
        <div class="transaction-info">
            <div class="transaction-category">${transaction.category_name}</div>
            <div class="transaction-note">${transaction.note || 'No note'}</div>
            <div class="transaction-date">${transaction.date}</div>
        </div>
        <div class="transaction-details">
            <div class="transaction-amount ${amountClass}">
                ${sign}${formattedAmount}
            </div>
            <div class="transaction-actions">
                <button class="action-btn edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                <button class="action-btn delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `;
    return listItem;
}

//New section

function loadRecentActivityCard() {
    const listElement = document.getElementById('recentTransactionsList');
    const cardElement = listElement.closest('.recent-transactions-card');
    if (!listElement || !cardElement) return;

    cardElement.style.display = 'block';
    listElement.innerHTML = '<li>Loading activity...</li>';

    // Fetch both recent transactions and all-time stats at the same time
    Promise.all([
        fetch('../api/user/get_recent_transactions.php?limit=3').then(res => res.json()),
        fetch('../api/user/get_all_time_stats.php').then(res => res.json())
    ])
    .then(([recentResult, statsResult]) => {
        listElement.innerHTML = ''; // Clear loading

        // 1. Display the 3 most recent transactions
        if (recentResult.success && recentResult.data.length > 0) {
            recentResult.data.forEach(transaction => {
                listElement.appendChild(createTransactionListItem(transaction));
            });
        }

        // 2. Display the all-time stats
        if (statsResult.success) {
            const { income, expense } = statsResult.data;
            const symbol = currencySymbols['USD'] || '$';

            // Add a separator if there were recent transactions
            if (listElement.children.length > 0) {
                listElement.innerHTML += '<hr class="stat-separator">';
            }

            if (income) {
                const date = new Date(income.date + 'T00:00:00').toLocaleDateString();
                const formattedIncome = convertAndFormat(parseFloat(income.amount), income.currency);
                listElement.innerHTML += `
                    <li class="all-time-stat income">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <strong>Top Income Ever:</strong> ${income.category_name}
                            <span class="stat-details">(${formattedIncome} on ${date})</span>
                        </div>
                    </li>
                `;
            }
            if (expense) {
                const date = new Date(expense.date + 'T00:00:00').toLocaleDateString();
                const formattedExpense = convertAndFormat(parseFloat(expense.amount), expense.currency);
                listElement.innerHTML += `
                    <li class="all-time-stat expense">
                        <i class="fas fa-fire-alt"></i>
                        <div>
                            <strong>Top Expense Ever:</strong> ${expense.category_name}
                            <span class="stat-details">(${formattedExpense} on ${date})</span>
                        </div>
                    </li>
                `;
            }
        }
        
        // Hide the card if it's completely empty
        if (listElement.children.length === 0) {
            cardElement.style.display = 'none';
        }
    })
    .catch(error => {
        console.error("Error loading recent activity card:", error);
        listElement.innerHTML = '<li>Error loading activity.</li>';
    });
}



function fetchTransactions() {
    loadRecentActivityCard();

    const loadingSpinner = document.getElementById('transactionsLoading');
    const noTransactionsMsg = document.getElementById('noTransactionsMessage');
    const monthlyTransactionsContainer = document.getElementById('monthlyTransactionsContainer');
    
    loadingSpinner.classList.remove('hidden');
    monthlyTransactionsContainer.innerHTML = '';
    noTransactionsMsg.style.display = 'none'; // Hide the old text message initially

    fetch('../api/user/get_monthly_transactions.php')
        .then(response => response.json())
        .then(result => {
            loadingSpinner.classList.add('hidden');
            if (result.success && Object.keys(result.data).length > 0) {
                const monthlyData = result.data;
                for (const monthName in monthlyData) {
                    const monthInfo = monthlyData[monthName];
                    const monthCard = document.createElement('div');
                    monthCard.className = 'card month-card';
                    
                    let summaryHTML = '';
                    if (monthInfo.top_income) {
                        const formattedTopIncome = convertAndFormat(monthInfo.top_income.amount, monthInfo.top_income.currency);
                        summaryHTML += `<div class="top-summary-item income"><i class="fas fa-arrow-up"></i> Top Income: <strong>${monthInfo.top_income.name}</strong> (${formattedTopIncome})</div>`;
                    }
                    if (monthInfo.top_expense) {
                        const formattedTopExpense = convertAndFormat(monthInfo.top_expense.amount, monthInfo.top_expense.currency);
                        summaryHTML += `<div class="top-summary-item expense"><i class="fas fa-arrow-down"></i> Top Expense: <strong>${monthInfo.top_expense.name}</strong> (${formattedTopExpense})</div>`;
                    }

                    monthCard.innerHTML = `
                        <h3>${monthName}</h3>
                        <div class="top-summary-container">${summaryHTML}</div>
                        <ul class="transaction-list"></ul>
                    `;
                    
                    const monthList = monthCard.querySelector('.transaction-list');
                    monthInfo.transactions.forEach(transaction => {
                        monthList.appendChild(createTransactionListItem(transaction));
                    });
                    
                    monthlyTransactionsContainer.appendChild(monthCard);
                }
            } else {
                monthlyTransactionsContainer.innerHTML = createEmptyState('fa-exchange-alt', 'No Transactions', 'Add a transaction to see your history.');
            }
        })
        .catch(error => {
            loadingSpinner.classList.add('hidden');
            monthlyTransactionsContainer.innerHTML = createEmptyState('fa-exclamation-triangle', 'Error', 'Could not load monthly transactions.');
            console.error('Fetch monthly transactions error:', error);
        });
}

// Ensure the event listener for edit/delete still works. 
// Attach it to a static parent container.
transactionsContainer.addEventListener('click', function(event) {
    // ... (Your existing event listener logic for edit/delete) ...
    // The code you already have for this should work fine, just ensure it's
    // attached to 'transactionsContainer' instead of 'transactionsList'.
});




// Add this new function to your dashboard.js file
function fetchAndDisplayBalances() {
    // Load the top summary cards (Savings Rate, Top Expense, etc.)
    loadFinancialSummary();
    // Load the recent transactions list
    populateRecentTransactions(5, 'balanceRecentTransactionsList');

    const container = document.getElementById('balancesContainer');
    
    fetch('../api/user/get_balances.php')
        .then(response => response.json())
        .then(result => {
            container.innerHTML = ''; // Clear previous balance cards
            if (result.success && result.data.length > 0) {
                result.data.forEach(item => {
                    const card = document.createElement('div');
                    const balanceState = parseFloat(item.balance) >= 0 ? 'positive' : 'negative';
                    card.className = `balance-card ${balanceState}`;
                    
                    // **THE FIX:** Use the new total_income and total_expense fields
                    const formattedBalance = convertAndFormat(parseFloat(item.balance), item.currency);
                    const formattedIncome = convertAndFormat(parseFloat(item.total_income), item.currency);
                    const formattedExpense = convertAndFormat(parseFloat(item.total_expense), item.currency);

                    card.innerHTML = `
                        <div class="balance-title">Balance</div>
                        <div class="balance-main">
                            <span class="balance-amount">${formattedBalance}</span>
                        </div>
                        <div class="balance-summary">
                            <div class="balance-summary-item">
                                <div class="title">Income</div>
                                <div class="amount">
                                    +${formattedIncome}
                                    <i class="fas fa-arrow-up arrow up"></i>
                                </div>
                            </div>
                            <div class="balance-summary-item">
                                <div class="title">Expense</div>
                                <div class="amount">
                                    -${formattedExpense}
                                    <i class="fas fa-arrow-down arrow down"></i>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(card);
                });
            } else {
                container.innerHTML = '<p>No balance information available to display.</p>';
            }
        })
        .catch(error => console.error('Error fetching balances:', error));
}



// === Event Listener for Dynamic Edit/Delete Buttons ===

// Attach a single listener to the static parent container
transactionsContainer.addEventListener('click', function (event) {
    const target = event.target;

    // Find the closest edit or delete button to the clicked element
    const editBtn = target.closest('.edit-btn');
    const deleteBtn = target.closest('.delete-btn');

    // --- Handle Edit Button Click ---
    if (editBtn) {
        const transactionItem = editBtn.closest('.transaction-item');
        const transactionId = transactionItem.dataset.id;

        // Fetch the details for this specific transaction
        fetch(`../api/user/get_transaction_details.php?id=${transactionId}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Open the modal and populate it with the fetched data
                    openTransactionModal(result.data);
                } else {
                    alert('Error: Could not fetch transaction details.');
                }
            })
            .catch(err => {
                console.error("Fetch details error:", err);
                alert('An error occurred while fetching details.');
            });
    }

    // --- Handle Delete Button Click ---
    if (deleteBtn) {
        const transactionItem = deleteBtn.closest('.transaction-item');
        const transactionId = transactionItem.dataset.id;

        // Show the confirmation dialog
        showConfirmationModal(() => {
            // This code runs only if the user clicks "Delete"
            fetch('../api/user/delete_transaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: transactionId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Refresh the entire transactions view
                    fetchTransactions(); 
                } else {
                    alert(`Error: ${result.error}`);
                }
            })
            .catch(err => {
                console.error("Delete error:", err);
                alert('An error occurred while deleting the transaction.');
            });
        });
    }
});


/**
 * Fetches a specific number of recent transactions and populates a given list.
 * @param {number} limit - The number of transactions to fetch.
 * @param {string} listElementId - The ID of the UL element to populate.
 */
function populateRecentTransactions(limit, listElementId) {
    const listElement = document.getElementById(listElementId);
    // Find the parent card to show/hide it
    const cardElement = listElement.closest('.recent-transactions-card');
    if (!listElement || !cardElement) return;

    // Show the card and a loading state
    cardElement.style.display = 'block';
    listElement.innerHTML = '<li>Loading...</li>';

    fetch(`../api/user/get_recent_transactions.php?limit=${limit}`)
        .then(response => response.json())
        .then(result => {
            listElement.innerHTML = ''; // Clear loading state
            if (result.success && result.data.length > 0) {
                result.data.forEach(transaction => {
                    // We already have a function for this!
                    listElement.appendChild(createTransactionListItem(transaction));
                });
            } else {
                cardElement.style.display = 'none'; // Hide card if no transactions
            }
        })
        .catch(error => {
            console.error(`Error fetching recent transactions for ${listElementId}:`, error);
            listElement.innerHTML = '<li>Could not load activity.</li>';
        });
}



// Keep track of chart instances to prevent duplicates
let myBarChart = null;
let myDoughnutChart = null;

function createBarChart(data) {
    const ctx = document.getElementById('monthlyBarChart').getContext('2d');
    if (myBarChart) {
        myBarChart.destroy();
    }
    myBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Income',
                    data: data.income,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                },
                {
                    label: 'Expenses',
                    data: data.expenses,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                },
                {
                    label: 'Net Income',
                    data: data.net,
                    borderColor: '#ffce56',
                    tension: 0.4,
                    type: 'line',
                    order: -1,
                    hidden: true
                }
            ]
        },
        plugins: [ChartDataLabels],
        options: {
            responsive: true,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            // Assuming chart data is always based on the primary currency (USD) for the axis
                            return convertAndFormat(value, 'USD'); 
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${convertAndFormat(context.parsed.y, 'USD')}`;
                        }
                    }
                },
                legend: {
                    labels: { usePointStyle: true, pointStyle: 'rect' },
                    onClick: (e, legendItem, legend) => {
                        const index = legendItem.datasetIndex;
                        const ci = legend.chart;
                        if (ci.isDatasetVisible(index)) {
                            ci.hide(index);
                            legendItem.hidden = true;
                        } else {
                            ci.show(index);
                            legendItem.hidden = false;
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: (value, context) => {
                        if (context.dataset.type === 'line') return null;
                        // **THE FIX:** Use the converter for the numbers on top of the bars
                        return value > 0 ? convertAndFormat(value, 'USD') : '';
                    },
                    font: {
                        weight: 'bold'
                    },
                    color: '#444'
                }
            }
        }
    });
}

function createDoughnutChart(data) {
    const ctx = document.getElementById('expenseDoughnutChart').getContext('2d');
    if (myDoughnutChart) {
        myDoughnutChart.destroy();
    }
    const chartData = data.map(item => parseFloat(item.total));
    const total = chartData.reduce((acc, value) => acc + value, 0);

    myDoughnutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.name),
            datasets: [{
                label: 'Expenses by Category',
                data: chartData,
                backgroundColor: [
                    '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff',
                    '#ff9f40', '#c9cbcf', '#f67019', '#f53794', '#537bc4'
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            if (isNaN(value)) return 'No data';
                            const percentage = ((value / total) * 100).toFixed(1);
                            // Convert tooltip values
                            const convertedValue = convertAndFormat(value, 'USD');
                            return `${context.label}: ${convertedValue} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}


function loadExpenseCharts() {
    loadComparisonData();

    Promise.all([
        fetch('../api/user/get_monthly_summary.php').then(res => res.json()),
        fetch('../api/user/get_expense_breakdown.php').then(res => res.json())
    ])
    .then(([summaryResult, breakdownResult]) => {
        if (summaryResult.success) {
            createBarChart(summaryResult.data);
        }
        if (breakdownResult.success) {
            createDoughnutChart(breakdownResult.data);
        }
    })
    .catch(error => console.error("Error loading chart data:", error));
}


function loadComparisonData() {
    const container = document.getElementById('comparisonContainer');
    container.innerHTML = 'Loading comparisons...';

    fetch('../api/user/get_comparison_data.php')
        .then(res => res.json())
        .then(result => {
            container.innerHTML = '';
            if (!result.success) return;

            const createCard = (title, value, iconClass, iconBgClass) => {
                const card = document.createElement('div');
                card.className = 'comparison-card';
                const isPositive = value >= 0;
                const isExpense = title.includes('Spending');
                
                let colorClass = 'positive';
                if ((isExpense && isPositive) || (!isExpense && !isPositive)) {
                    colorClass = 'negative';
                }

                const trendIcon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';

                card.innerHTML = `
                    <div class="icon-container ${iconBgClass}">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="title">${title} vs. Last Month</div>
                    <div class="percentage ${colorClass}">
                        <i class="fas ${trendIcon} icon"></i>
                        <span>${Math.abs(value).toFixed(1)}%</span>
                    </div>
                `;
                return card;
            };

            // Create cards with the new background class parameter
            container.appendChild(createCard('Spending', result.data.expense_change, 'fa-shopping-cart', 'icon-spending'));
            container.appendChild(createCard('Income', result.data.income_change, 'fa-dollar-sign', 'icon-income'));
            container.appendChild(createCard('Savings', result.data.savings_change, 'fa-piggy-bank', 'icon-savings-comp'));
        });
}


function createBalanceExpenseChart(data) {
    const ctx = document.getElementById('balanceExpenseChart').getContext('2d');
    if (myBalanceExpenseChart) {
        myBalanceExpenseChart.destroy();
    }
    myBalanceExpenseChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.name),
            datasets: [{
                data: data.map(item => parseFloat(item.total)),
                backgroundColor: [
                    '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff',
                    '#ff9f40', '#c9cbcf', '#f67019', '#f53794', '#537bc4'
                ],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false } // Hide legend for a cleaner look
            }
        }
    });
}



function loadFinancialSummary() {
    const container = document.getElementById('financialSummaryContainer');
    container.innerHTML = '';

    fetch('../api/user/get_financial_summary.php')
        .then(res => res.json())
        .then(result => {
            if (!result.success) return;

            const data = result.data;
            const currencySymbols = { USD: '$', EUR: '€', VND: '₫' };
            
            // --- Card 1: Savings Rate ---
            const savingsCard = document.createElement('div');
            savingsCard.className = 'summary-card';
            savingsCard.innerHTML = `
                <div class="icon-container icon-savings">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <div class="details">
                    <div class="title">Savings Rate</div>
                    <div class="value">${data.savings_rate.toFixed(1)}%</div>
                </div>
            `;
            container.appendChild(savingsCard);

            // --- Card 2: Largest Expense ---
            const largestExpenseCard = document.createElement('div');
            largestExpenseCard.className = 'summary-card';
            let largestExpenseContent;
            if (data.largest_expense && data.largest_expense.amount) {
                // Bug Fix: We didn't query for currency, so get it from the note or assume a default.
                // For a robust solution, you would add currency to the get_financial_summary.php query.
                const symbol = currencySymbols['USD'] || '$'; // Assuming USD for now
                largestExpenseContent = `
                    <div class="value">${symbol}${parseFloat(data.largest_expense.amount).toFixed(2)}</div>
                    <div class="context">${data.largest_expense.note || 'Largest single expense'}</div>
                `;
            } else {
                largestExpenseContent = `<div class="value">-</div>`;
            }
            largestExpenseCard.innerHTML = `
                <div class="icon-container icon-expense">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="details">
                    <div class="title">Top Expense</div>
                    ${largestExpenseContent}
                </div>
            `;
            container.appendChild(largestExpenseCard);

            // --- Card 3: Biggest Spending Day ---
            const biggestDayCard = document.createElement('div');
            biggestDayCard.className = 'summary-card';
            let biggestDayContent;
            if (data.biggest_spending_day && data.biggest_spending_day.daily_total) {
                const day = new Date(data.biggest_spending_day.date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long' });
                const symbol = currencySymbols['USD'] || '$'; // Assuming USD for now
                biggestDayContent = `
                    <div class="value">${day}</div>
                    <div class="context">Spent ${symbol}${parseFloat(data.biggest_spending_day.daily_total).toFixed(2)}</div>
                `;
            } else {
                biggestDayContent = `<div class="value">-</div>`;
            }
            biggestDayCard.innerHTML = `
                <div class="icon-container icon-day">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="details">
                    <div class="title">Busiest Day</div>
                    ${biggestDayContent}
                </div>
            `;
            container.appendChild(biggestDayCard);
        });
}


// --- Settings Page Logic ---

// Load User Details
// --- Settings Page Logic ---

// --- Settings Page Logic ---
// --- Original Settings Page Logic ---

function loadUserDetails() {
    fetch('../api/user/get_user_details.php')
        .then(res => res.json())
        .then(result => {
            if(result.success) {
                const user = result.data;
                const defaultAvatarSVG = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23a0aec0'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E";
                const avatarUrl = user.avatar_url ? `../public/${user.avatar_url}` : defaultAvatarSVG;
                
                document.getElementById('sidebarAvatar').src = avatarUrl;
                document.getElementById('sidebarUsername').textContent = user.username;
                document.getElementById('username').textContent = user.username;
                document.getElementById('settingsAvatarPreview').src = avatarUrl;
                document.getElementById('usernameInput').value = user.username;
            }
        });
}

const profileSettingsForm = document.getElementById('profileSettingsForm');
if (profileSettingsForm) {
    // Handle image preview
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('settingsAvatarPreview');
    avatarInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Handle form submission for username and avatar
    profileSettingsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('../api/user/update_profile.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if(result.success) {
                    alert(result.message);
                    loadUserDetails();
                } else {
                    alert('Error: ' + result.error);
                }
            });
    });
}

// Handle Remove Avatar Button
document.getElementById('removeAvatarBtn')?.addEventListener('click', function() {
    if (confirm('Are you sure you want to remove your avatar?')) {
        const formData = new FormData();
        formData.append('action', 'remove_avatar');
        fetch('../api/user/update_profile.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if(result.success) {
                    alert(result.message);
                    loadUserDetails();
                }
            });
    }
});


// load overviewstat

function loadOverviewStats() {
    const container = document.getElementById('overviewStatsContainer');
    // This line fixes the duplicate card issue by clearing the container first
    container.innerHTML = ''; 

    fetch('../api/user/get_overview_stats.php')
        .then(res => res.json())
        .then(result => {
            if (!result.success) return;
            const data = result.data;

            const createStatCard = (iconClass, iconBgClass, title, value, currency, valueClass = '') => {
                const card = document.createElement('div');
                card.className = 'stat-card';
                const formattedValue = convertAndFormat(value, currency);
                card.innerHTML = `
                    <div class="icon-container ${iconBgClass}">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="details">
                        <div class="title">${title}</div>
                        <div class="value ${valueClass}">${formattedValue}</div>
                    </div>
                `;
                return card;
            };

            const netClass = data.month_net >= 0 ? 'positive' : 'negative';

            container.appendChild(createStatCard('fa-wallet', 'icon-balance', 'Current Balance (USD)', data.current_balance, 'USD'));
            container.appendChild(createStatCard('fa-chart-line', 'icon-net', "This Month's Net", data.month_net, 'USD', netClass));
            container.appendChild(createStatCard('fa-shopping-cart', 'icon-spending', "This Month's Spending", data.month_spending, 'USD'));
        });
}



/**
 * Returns a Font Awesome icon class based on the category name.
 * @param {string} categoryName - The name of the category.
 * @returns {string} - A Font Awesome class string (e.g., 'fas fa-utensils').
 */
function getIconForCategory(categoryName) {
    const name = categoryName.toLowerCase();
    // Keywords to check for in the category name
    if (name.includes('food') || name.includes('groceries') || name.includes('dining')) return 'fas fa-utensils';
    if (name.includes('salary') || name.includes('freelance') || name.includes('income')) return 'fas fa-dollar-sign';
    if (name.includes('rent') || name.includes('mortgage')) return 'fas fa-home';
    if (name.includes('transport')) return 'fas fa-bus';
    if (name.includes('shopping') || name.includes('bills')) return 'fas fa-shopping-bag';
    if (name.includes('health') || name.includes('medical')) return 'fas fa-heartbeat';
    if (name.includes('entertainment')) return 'fas fa-film';
    if (name.includes('utilities')) return 'fas fa-lightbulb';
    if (name.includes('travel')) return 'fas fa-plane';
    if (name.includes('education')) return 'fas fa-graduation-cap';
    if (name.includes('pets')) return 'fas fa-paw';
    // A default icon if no keyword is matched
    return 'fas fa-tag';
}

function scrollToTransaction(transactionId) {
    const transactionItem = document.querySelector(`.transaction-item[data-id="${transactionId}"]`);
    if (transactionItem) {
        transactionItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Add a temporary highlight effect
        transactionItem.style.transition = 'background-color 0.3s ease';
        transactionItem.style.backgroundColor = '#e3f2fd';
        setTimeout(() => {
            transactionItem.style.backgroundColor = ''; // Reset background
        }, 1500);
    }
}


const billModal = document.getElementById('billModal');
const billForm = document.getElementById('billForm');

function openBillModal(billData = null) {
    const categorySelect = document.getElementById('billCategory');
    const modalTitle = billModal.querySelector('h2');
    const billIdInput = document.getElementById('billId');
    billForm.reset();

    fetch('../api/user/get_categories.php')
        .then(res => res.json())
        .then(result => {
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            if (result.success) {
                result.data.forEach(cat => {
                    if (cat.type === 'expense') {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.name;
                        categorySelect.appendChild(option);
                    }
                });
            }
        })
        .then(() => {
            if (billData) {
                modalTitle.textContent = 'Edit Recurring Bill';
                billIdInput.value = billData.id;
                document.getElementById('billName').value = billData.name;
                document.getElementById('billAmount').value = billData.amount;
                document.getElementById('billDueDate').value = billData.due_date;
                categorySelect.value = billData.category_id;
            } else {
                modalTitle.textContent = 'Add Recurring Bill';
                billIdInput.value = '';
            }
        });
    billModal.style.display = 'flex';
}

function closeBillModal() {
    billModal.style.display = 'none';
}

function fetchBills() {
    const upcomingList = document.getElementById('upcomingBillsList');
    const paidList = document.getElementById('paidBillsList');
    upcomingList.innerHTML = '<li>Loading...</li>';
    paidList.innerHTML = ''; // Start paid list empty

    fetch('../api/user/get_bills.php')
        .then(res => res.json())
        .then(result => {
            upcomingList.innerHTML = '';
            paidList.innerHTML = '';
            if (!result.success) {
                upcomingList.innerHTML = createEmptyState('fa-file-invoice-dollar', 'Could Not Load Bills', 'There was an error fetching your bill data.');
                return;
            };
            
            const currentMonth = new Date().toISOString().slice(0, 7);
            let upcomingCount = 0;
            let paidCount = 0;

            result.data.forEach(bill => {
                const isPaid = bill.last_paid_month === currentMonth;
                const listItem = document.createElement('li');
                listItem.className = `bill-item ${isPaid ? 'paid' : 'upcoming'}`;
                const iconClass = isPaid ? 'fa-check' : 'fa-clock';
                
                let actionButtons = '';
                if (isPaid) {
                    actionButtons = `<button class="btn unpay-bill-btn" data-id="${bill.id}">Unpay</button>`;
                } else {
                    actionButtons = `
                        <button class="btn mark-paid-btn" data-id="${bill.id}">Pay Bill</button>
                        <button class="action-btn edit-bill-btn" data-id="${bill.id}" title="Edit Bill"><i class="fas fa-edit"></i></button>
                        <button class="action-btn delete-bill-btn" data-id="${bill.id}" title="Delete Bill"><i class="fas fa-trash"></i></button>
                    `;
                }

                listItem.innerHTML = `
                    <div class="bill-info">
                        <div class="bill-icon"><i class="fas ${iconClass}"></i></div>
                        <div>
                            <div class="bill-name">${bill.name}</div>
                            <div class="bill-details">Due on day ${bill.due_date}</div>
                        </div>
                    </div>
                    <div class="bill-actions">
                        <span class="bill-amount">${convertAndFormat(parseFloat(bill.amount), 'USD')}</span>
                        ${actionButtons}
                    </div>
                `;
                if(isPaid) {
                    paidList.appendChild(listItem);
                    paidCount++;
                } else {
                    upcomingList.appendChild(listItem);
                    upcomingCount++;
                }
            });

            if (upcomingCount === 0) {
                upcomingList.innerHTML = createEmptyState('fa-check-circle', 'All Bills Paid!', 'You have no upcoming bills for this month.');
            }
            if (paidCount === 0) {
                paidList.innerHTML = createEmptyState('fa-folder-open', 'No Paid Bills', 'Bills you pay this month will appear here.');
            }
        });
}
// Main click listener for the entire document
// This single listener handles clicks for transactions, bills, and goals
document.addEventListener('click', function(e) {
    // Transaction buttons
    const editTransactionBtn = e.target.closest('.edit-btn');
    const deleteTransactionBtn = e.target.closest('.delete-btn');
    if (editTransactionBtn) {
        const transactionId = editTransactionBtn.closest('.transaction-item').dataset.id;
        fetch(`../api/user/get_transaction_details.php?id=${transactionId}`)
            .then(res => res.json())
            .then(result => { if (result.success) openTransactionModal(result.data); });
    }
    if (deleteTransactionBtn) {
        const transactionId = deleteTransactionBtn.closest('.transaction-item').dataset.id;
        showConfirmationModal(() => {
            fetch('../api/user/delete_transaction.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: transactionId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Refresh the view where the button was clicked
                    const activeSectionId = document.querySelector('.content-section.active-section')?.id.replace('-section', '');
                    if (activeSectionId === 'transactions') fetchTransactions();
                    if (activeSectionId === 'balances') fetchAndDisplayBalances();
                } else {
                    alert(`Error: ${result.error}`);
                }
            });
        });
    }

    // Bill buttons
    const payBtn = e.target.closest('.mark-paid-btn');
    const deleteBillBtn = e.target.closest('.delete-bill-btn');
    const editBillBtn = e.target.closest('.edit-bill-btn');
    const unpayBtn = e.target.closest('.unpay-bill-btn');

    if (payBtn) {
        const billId = payBtn.dataset.id;
        if(confirm('This will create a new expense transaction. Are you sure?')) {
            fetch('../api/user/pay_bill.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: billId })})
            .then(res => res.json()).then(result => { if (result.success) fetchBills(); else alert('Error: ' + result.error); });
        }
    }
    if (deleteBillBtn) {
        const billId = deleteBillBtn.dataset.id;
        if(confirm('Are you sure you want to permanently delete this recurring bill?')) {
            fetch('../api/user/delete_bill.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: billId })})
            .then(res => res.json()).then(result => { if (result.success) fetchBills(); else alert('Error: ' + result.error); });
        }
    }
    if (editBillBtn) {
        const billId = editBillBtn.dataset.id;
        fetch(`../api/user/get_bill_details.php?id=${billId}`)
            .then(res => res.json())
            .then(result => { if (result.success) openBillModal(result.data); });
    }
    if (unpayBtn) {
        const billId = unpayBtn.dataset.id;
        if (confirm('Are you sure you want to mark this bill as unpaid?')) {
            fetch('../api/user/unpay_bill.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: billId })})
            .then(res => res.json()).then(result => { if (result.success) fetchBills(); else alert('Error: ' + result.error); });
        }
    }

    // Goal buttons
    const contributionBtn = e.target.closest('.add-contribution-btn');
    const deleteGoalBtn = e.target.closest('.delete-goal-btn');

    if (contributionBtn) {
        const goalId = contributionBtn.dataset.id;
        const goalName = contributionBtn.dataset.name;
        openContributionModal(goalId, goalName);
    }
    if (deleteGoalBtn) {
        const goalId = deleteGoalBtn.dataset.id;
        if (confirm('Are you sure you want to permanently delete this goal and all its contributions?')) {
            fetch('../api/user/delete_goal.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: goalId })})
            .then(res => res.json())
            .then(result => { if (result.success) fetchGoals(); else alert('Error: ' + result.error); });
        }
    }

    const editBudgetBtn = e.target.closest('.edit-budget-btn');
    const deleteBudgetBtn = e.target.closest('.delete-budget-btn');

    if (editBudgetBtn) {
        const categoryId = editBudgetBtn.dataset.id;
        fetch(`../api/user/get_budget_details.php?category_id=${categoryId}`)
            .then(res => res.json())
            .then(result => {
                if (result.success) openBudgetModal(result.data);
            });
    }

    if (deleteBudgetBtn) {
        const categoryId = deleteBudgetBtn.dataset.id;
        if (confirm('Are you sure you want to delete this budget?')) {
            fetch('../api/user/delete_budget.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ category_id: categoryId })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) fetchBudgets();
                else alert('Error: ' + result.error);
            });
        }
    }


});

// Bill form submission listener
billForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const billId = formData.get('bill_id');
    const url = billId ? '../api/user/update_bill.php' : '../api/user/add_bill.php';
    
    fetch(url, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            closeBillModal();
            fetchBills();
        } else {
            alert('Error: ' + result.error);
        }
    });
});


function createTopExpensesChart(data) {
    const ctx = document.getElementById('topExpensesChart').getContext('2d');
    if (myTopExpensesChart) {
        myTopExpensesChart.destroy();
    }
    myTopExpensesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.name),
            datasets: [{
                label: 'Total Spent',
                data: data.map(item => item.total),
                backgroundColor: '#3498db',
            }]
        },
        options: {
            indexAxis: 'y', // Makes it a horizontal bar chart
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => convertAndFormat(context.parsed.x, 'USD')
                    }
                }
            }
        }
    });
}




const goalModal = document.getElementById('goalModal');
const goalForm = document.getElementById('goalForm');
const contributionModal = document.getElementById('contributionModal');
const contributionForm = document.getElementById('contributionForm');

function openGoalModal() { goalModal.style.display = 'flex'; }
function closeGoalModal() { goalModal.style.display = 'none'; }
function openContributionModal(goalId, goalName) {
    contributionForm.reset();
    document.getElementById('contributionGoalId').value = goalId;
    document.getElementById('contributionModalTitle').textContent = `Contribute to: ${goalName}`;
    contributionModal.style.display = 'flex';
}
function closeContributionModal() { contributionModal.style.display = 'none'; }

function fetchGoals() {
    const container = document.getElementById('goalsContainer');
    container.innerHTML = 'Loading goals...';

    fetch('../api/user/get_goals.php')
        .then(res => res.json())
        .then(result => {
            container.innerHTML = '';
            if (!result.success || result.data.length === 0) {
                container.innerHTML = createEmptyState('fa-bullseye', 'No Goals Yet', 'Add a new goal to start tracking your progress.');
                return;
            }
            result.data.forEach((goal, index) => {
                const saved = parseFloat(goal.saved_amount);
                const target = parseFloat(goal.target_amount);
                const progress = target > 0 ? (saved / target) * 100 : 0;
                const iconColor = GOAL_ICON_COLORS[index % GOAL_ICON_COLORS.length];
                const card = document.createElement('div');
                card.className = 'goal-card';
                const formattedSaved = convertAndFormat(saved, 'USD');
                const formattedTarget = convertAndFormat(target, 'USD');

                card.innerHTML = `
                    <div class="goal-header">
                        <div class="goal-icon-name">
                            <i class="fas fa-bullseye" style="color: ${iconColor};"></i>
                            <span class="goal-name">${goal.name}</span>
                        </div>
                        <div class="goal-actions">
                            <button class="add-contribution-btn" data-id="${goal.id}" data-name="${goal.name}" title="Add Funds">+</button>
                            <button class="delete-goal-btn" data-id="${goal.id}" title="Delete Goal">&times;</button>
                        </div>
                    </div>
                    <div class="goal-percentage">${progress.toFixed(1)}% Complete</div>
                    <div class="goal-progress-bar">
                        <div class="goal-progress-fill" style="width: ${progress}%;"></div>
                    </div>
                    <div class="goal-amounts">
                        <span class="goal-saved-amount">${formattedSaved}</span>
                        <span>Target: ${formattedTarget}</span>
                    </div>
                `;
                container.appendChild(card);
            });
        });
}

// Event listener for contribution buttons
document.addEventListener('click', function(e){
    if (e.target.classList.contains('add-contribution-btn')) {
        const goalId = e.target.dataset.id;
        const goalName = e.target.dataset.name;
        openContributionModal(goalId, goalName);
    }

    if (e.target.closest('.delete-goal-btn')) {
        const btn = e.target.closest('.delete-goal-btn');
        const goalId = btn.dataset.id;
        if (confirm('Are you sure you want to permanently delete this goal and all its contributions?')) {
            fetch('../api/user/delete_goal.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: goalId })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    fetchGoals(); // Refresh the list
                } else {
                    alert('Error: ' + result.error);
                }
            });
        }
    }
});

// Handle form submissions
goalForm.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('../api/user/add_goal.php', { method: 'POST', body: new FormData(this) })
        .then(res => res.json()).then(result => {
            if(result.success) { closeGoalModal(); fetchGoals(); } else { alert('Error: ' + result.error); }
        });
});

contributionForm.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('../api/user/add_contribution.php', { method: 'POST', body: new FormData(this) })
        .then(res => res.json()).then(result => {
            if(result.success) { closeContributionModal(); fetchGoals(); } else { alert('Error: ' + result.error); }
        });
});



/**
 * Generates and returns the HTML for a professional empty state message.
 * @param {string} iconClass - A Font Awesome icon class (e.g., 'fa-folder-open').
 * @param {string} title - The main heading text.
 * @param {string} text - The descriptive subtext.
 * @returns {string} - The complete HTML string for the empty state component.
 */
function createEmptyState(iconClass, title, text) {
    return `
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas ${iconClass}"></i></div>
            <h4 class="empty-state-title">${title}</h4>
            <p class="empty-state-text">${text}</p>
        </div>
    `;
}


const budgetModal = document.getElementById('budgetModal');
const budgetForm = document.getElementById('budgetForm');

function openBudgetModal(budgetData = null) {
    budgetForm.reset();
    const categorySelect = document.getElementById('budgetCategory');
    const modalTitle = budgetModal.querySelector('h2');
    
    // Clear any existing hidden input
    const existingHidden = budgetForm.querySelector('input[name="category_id_hidden"]');
    if (existingHidden) existingHidden.remove();
    
    categorySelect.innerHTML = '<option>Loading...</option>';
    
    fetch('../api/user/get_categories.php')
        .then(res => res.json())
        .then(result => {
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            if (result.success) {
                result.data.forEach(cat => {
                    if (cat.type === 'expense') {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.name;
                        categorySelect.appendChild(option);
                    }
                });
            }
        })
        .then(() => {
            if (budgetData) {
                modalTitle.textContent = 'Edit Budget';
                categorySelect.value = budgetData.category_id;
                categorySelect.disabled = true;
                document.getElementById('budgetAmount').value = budgetData.budget_amount;
                
                // **THE FIX:** Add a hidden input to submit the disabled category ID
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'category_id'; // Use the name the api expects
                hiddenInput.value = budgetData.category_id;
                budgetForm.appendChild(hiddenInput);
                
            } else {
                modalTitle.textContent = 'Set a Monthly Budget';
                categorySelect.disabled = false;
            }
        });
    
    budgetModal.style.display = 'flex';
}
function closeBudgetModal() {
    budgetModal.style.display = 'none';
}

function fetchBudgets() {
    const container = document.getElementById('budgetsContainer');
    container.innerHTML = 'Loading budgets...';

    fetch('../api/user/get_budgets.php')
        .then(res => res.json())
        .then(result => {
            container.innerHTML = '';
            if (!result.success || result.data.length === 0) {
                container.innerHTML = createEmptyState('fa-tasks', 'No Budgets Set', 'Set a budget for a category to track your spending.');
                return;
            }
            result.data.forEach(budget => {
                const spent = parseFloat(budget.spent_amount);
                const total = parseFloat(budget.budget_amount);
                let progress = total > 0 ? (spent / total) * 100 : 0;

                let overBudgetClass = '';
                if (progress > 100) {
                    overBudgetClass = 'over-budget';
                    progress = 100;
                }

                const iconClass = getIconForCategory(budget.category_name);
                const colorClass = getCategoryColorClass(budget.category_name);

                const card = document.createElement('div');
                card.className = 'budget-card';
                card.innerHTML = `
                    <div class="budget-header">
                        <div class="budget-category-info">
                            <div class="budget-icon ${colorClass}"><i class="fas ${iconClass}"></i></div>
                            <span class="budget-category-name">${budget.category_name}</span>
                        </div>
                        <div class="budget-actions">
                            <button class="action-btn edit-budget-btn" data-id="${budget.category_id}" title="Edit Budget"><i class="fas fa-edit"></i></button>
                            <button class="action-btn delete-budget-btn" data-id="${budget.category_id}" title="Delete Budget"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="budget-amounts">
                        <span class="budget-spent">${convertAndFormat(spent, 'USD')}</span> / ${convertAndFormat(total, 'USD')}
                    </span>
                    <div class="budget-progress-bar">
                        <div class="budget-progress-fill ${overBudgetClass}" style="width: ${progress}%;"></div>
                    </div>
                `;
                container.appendChild(card);
            });
        });
}

// Handle form submission
budgetForm.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('../api/user/set_budget.php', { method: 'POST', body: new FormData(this) })
        .then(res => res.json()).then(result => {
            if(result.success) {
                closeBudgetModal();
                fetchBudgets();
            } else { alert('Error: ' + result.error); }
        });
});



function generateReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    if (!startDate || !endDate) {
        alert('Please select both a start and end date.');
        return;
    }

    const reportOutput = document.getElementById('reportOutput');
    const reportContent = document.getElementById('reportContent');
    reportContent.innerHTML = 'Generating report...';
    reportOutput.classList.remove('hidden');

    fetch(`../api/user/get_report_data.php?start_date=${startDate}&end_date=${endDate}`)
        .then(res => res.json())
        .then(result => {
            if (!result.success) {
                reportContent.innerHTML = `<p>Error: ${result.error}</p>`;
                return;
            }
            
            const data = result.data;
            const summary = data.summary;
            const breakdown = data.expense_breakdown;
            const netClass = summary.net >= 0 ? 'positive' : 'negative';

            let breakdownHTML = '<h4>No expense data for this period.</h4>';
            if (breakdown.length > 0) {
                breakdownHTML = `
                    <h4>Expense Breakdown</h4>
                    <table class="report-breakdown-table">
                        <tbody>
                            ${breakdown.map(item => `
                                <tr>
                                    <td>${item.category_name}</td>
                                    <td><strong>${convertAndFormat(parseFloat(item.total), 'USD')}</strong></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            reportContent.innerHTML = `
                <div class="report-summary-grid">
                    <div class="report-summary-item">
                        <div class="icon icon-income"><i class="fas fa-arrow-up"></i></div>
                        <div>
                            <h4>Total Income</h4>
                            <div class="value positive">${convertAndFormat(summary.income, 'USD')}</div>
                        </div>
                    </div>
                    <div class="report-summary-item">
                        <div class="icon icon-expense"><i class="fas fa-arrow-down"></i></div>
                        <div>
                            <h4>Total Expense</h4>
                            <div class="value negative">${convertAndFormat(summary.expense, 'USD')}</div>
                        </div>
                    </div>
                    <div class="report-summary-item">
                        <div class="icon icon-net"><i class="fas fa-balance-scale"></i></div>
                        <div>
                            <h4>Net Savings</h4>
                            <div class="value ${netClass}">${convertAndFormat(summary.net, 'USD')}</div>
                        </div>
                    </div>
                </div>
                <div class="report-breakdown">
                    ${breakdownHTML}
                </div>
            `;
        });
}

function downloadReportAsPDF() {
    const { jsPDF } = window.jspdf;
    const reportContent = document.getElementById('reportContent');
    
    html2canvas(reportContent, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('financial-report.pdf');
    });
}

