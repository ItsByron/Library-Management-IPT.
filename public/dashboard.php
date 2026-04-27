<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
require_once '../database/db.php'; 
// Protect page (must be logged in)
// if (!isset($_SESSION['admin'])) {
//     header("Location: login.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AlgoReadthm — Library Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Crimson+Pro:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/DashboardStyle.css"/>
  <link rel="stylesheet" href="assets/toast.css"/>
</head>
<body>

<header class="site-header">
  <div class="d-flex align-items-center justify-content-between px-4 py-0" style="min-height:68px;">
    <div class="d-flex flex-column py-3">
      <span class="logo-title">AlgoReadthm</span>
      <span class="logo-sub">Library Management System</span>
    </div>
    <nav class="site-nav d-flex gap-1 flex-wrap">
      <button class="btn-nav active" onclick="showSection('catalog',this)">Catalog</button>
      <button class="btn-nav" onclick="showSection('add',this)">Add Book</button>
      <button class="btn-nav" onclick="showSection('members',this)">Members</button>
      <button class="btn-nav" onclick="showSection('borrowed',this)">Borrowed</button>
      <button class="btn-nav" onclick="showSection('history',this)">History</button>
      <!-- <button class="btn-nav" onclick="showSection('fines',this)">Fines</button> -->
      <button class="btn-nav btn-logout" onclick="logout()">Logout</button>
    </nav>
  </div>
</header>

<div class="d-flex" style="min-height:calc(100vh - 68px)">
  <main class="flex-fill p-4" style="overflow:auto">
    <div id="section-catalog" class="section active">
      <div class="section-header"><h2>Book Catalog</h2></div>
      <div class="mb-3">
        <div class="d-flex gap-3 flex-wrap align-items-center">
          <div class="search-box flex-fill" style="min-width:200px">
            <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="searchInput" placeholder="Title, author, ISBN, genre…" oninput="debounceSearch('catalog')"/>
          </div>
          <div class="mb-2 text-muted" id="bookCount"></div>
          <!-- <select class="filter-select" id="availFilter"> -->
            <!-- renderCatalog -->
             <!-- onchange="loadBooks()" -->
            <!-- <option value="">All</option>
            <option value="available">Available</option>
            <option value="borrowed">Borrowed</option>
          </select> -->
          <div class="row g-3" id="bookGrid"></div>
        </div>
      </div>
      <!-- <div class="book-grid" id="bookGrid"></div> -->
    </div>

    <div id="section-add" class="section">
      <div class="section-header"><h2>Add New Book</h2></div>
      <div class="form-card">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label-upper d-block mb-1">Title *</label>
            <input type="text" id="f-title" class="form-control" placeholder="The Great Gatsby"/>
          </div>
          <div class="col-md-6">
            <label class="form-label-upper d-block mb-1">Author *</label>
            <input type="text" id="f-author" class="form-control" placeholder="F. Scott Fitzgerald"/>
          </div>
          <div class="col-md-6">
            <label class="form-label-upper d-block mb-1">ISBN</label>
            <input type="text" id="f-isbn" class="form-control" placeholder="978-…"/>
          </div>
          <div class="col-md-6">
            <label class="form-label-upper d-block mb-1">Category</label>
            <select id="f-genre" class="form-select">
              <option>Fiction</option><option>Non-Fiction</option><option>Science</option>
              <option>History</option><option>Philosophy</option><option>Fantasy</option><option>Mystery</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label-upper d-block mb-1">Year</label>
            <input type="number" id="f-year" class="form-control" placeholder="1925" min="1000" max="2099"/>
          </div>
          <div class="col-md-3">
            <label class="form-label-upper d-block mb-1">Copies to add</label>
            <input type="number" id="f-copies" class="form-control" value="1" min="1" max="20"/>
          </div>
        </div>
        <button class="btn-submit mt-4" onclick="addBook()">Add to Catalog</button>
      </div>
    </div>

    <div id="section-members" class="section">
      <div class="section-header"><h2>Members</h2></div>
      <div class="card border-0 shadow-sm mb-4 p-3" style="background:#fff;border-radius:8px;">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label-upper d-block mb-1">Name *</label>
            <input type="text" id="m-name" class="form-control" placeholder="Jane Doe"/>
          </div>
          <div class="col-md-3">
            <label class="form-label-upper d-block mb-1">Email</label>
            <input type="email" id="m-email" class="form-control" placeholder="jane@example.com" autocomplete="off"/>
          </div>
          <div class="col-md-3">
            <label class="form-label-upper d-block mb-1">Contact Number</label>
            <input type="tel" id="m-contact" class="form-control" placeholder="+63 912 345 6789"/>
          </div>
          <div class="col-md-3">
            <button class="btn-submit w-100" style="margin-top:0" onclick="addMember()">+ Add Member</button>
          </div>
        </div>
      </div>
      <div class="mb-3">
        <div class="d-flex gap-3 flex-wrap align-items-center">
          <div class="search-box flex-fill" style="min-width:200px">
            <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="memberSearch" placeholder="Search by name, email, or contact…" oninput="debounceSearch('members')" autocomplete="off"/>
          </div>
          <select class="filter-select" id="memberBooksFilter" onchange="renderMembers()">
            <option value="">All Members</option>
            <option value="active">Has Books Out</option>
            <option value="clear">No Books Out</option>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead><tr>
            <th>Member_ID</th>
            <th>Member_Name</th>
            <th>Email</th>
            <th>Contact_Number</th>
            <th>Date_Joined</th>
            <th class="text-center">Books Out</th>
            <th>Member_Status_Name</th>
            <th>Actions</th>
          </tr></thead>
          <tbody id="memberTable"></tbody>
        </table>
      </div>
    </div>


    <div id="section-borrowed" class="section">
      <div class="section-header"><h2>Borrowed Books</h2></div>
      <div class="mb-3">
        <div class="d-flex gap-3 flex-wrap align-items-center">
          <div class="search-box flex-fill" style="min-width:200px">
            <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="borrowedSearch" placeholder="Search by title, author, or borrower…" oninput="renderBorrowed()"/>
          </div>
          <select class="filter-select" id="borrowedStatusFilter" onchange="renderBorrowed()">
            <option value="">All</option>
            <option value="overdue">Overdue</option>
            <option value="current">Not Overdue</option>
          </select>
        </div>
        <div class="result-count" id="borrowedResultCount"></div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead><tr>
            <th>Borrow_ID</th>
            <th>BookCopy_ID</th>
            <th>Book_Title</th>
            <th>Author_Name</th>
            <th>Member_ID</th>
            <th>Member_Name</th>
            <th>Borrow_Date</th>
            <th>Due_Date</th>
            <th>Actions</th>
          </tr></thead>
          <tbody id="borrowTable"></tbody>
        </table>
      </div>
    </div>


    <div id="section-history" class="section">
      <div class="section-header"><h2>Transaction History</h2></div>
      <div class="mb-3">
        <div class="d-flex gap-3 flex-wrap align-items-center">
          <div class="search-box flex-fill" style="min-width:200px">
            <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="historySearch" placeholder="Search by Book Title or Member Name" oninput="debounceSearch('history')"/>
          </div>
          <select class="filter-select" id="historyTypeFilter" onchange="renderHistory()">
            <option value="">All Transactions</option>
            <option value="borrowed">Borrowed</option>
            <option value="returned">Returned</option>
          </select>
        </div>
        <div class="result-count" id="historyResultCount"></div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead><tr>
            <th>BorrowDetails_ID</th>
            <th>Borrow_ID</th>
            <th>BookCopy_ID</th>
            <th>Book_Title</th>
            <th>Member_ID</th>
            <th>Member_Name</th>
            <th>Borrow_Date</th>
            <th>Return_Date</th>
            <th>Borrow_Status_Name</th>
            <th>Actions</th>
          </tr></thead>
          <tbody id="historyTable"></tbody>
        </table>
      </div>
    </div>


    <div id="section-fines" class="section">
      <div class="section-header"><h2>Fines</h2></div>
      <div class="info-banner">
        <strong>How fines work:</strong> A fine of <strong>₱5.00 per day</strong> is automatically created when a book is returned after its due date.
        You can mark a fine as <em>Paid</em> (money collected) or <em>Waived</em> (forgiven).
      </div>
      <div class="mb-3">
        <div class="d-flex gap-3 flex-wrap align-items-center">
          <div class="search-box flex-fill" style="min-width:200px">
            <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="finesSearch" placeholder="Search by member name or book title…" oninput="renderFines()"/>
          </div>
          <select class="filter-select" id="finesStatusFilter" onchange="renderFines()">
            <option value="">All Fines</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Paid">Paid</option>
            <option value="Waived">Waived</option>
          </select>
        </div>
        <div class="result-count" id="finesResultCount"></div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead><tr>
            <th>Fines_ID</th>
            <th>BorrowDetails_ID</th>
            <th>Member_Name</th>
            <th>Book_Title</th>
            <th>Fine_Amount</th>
            <th>Issued_Date</th>
            <th>Paid_Date</th>
            <th>Fine_Status_Name</th>
            <th>Actions</th>
          </tr></thead>
          <tbody id="finesTable"></tbody>
        </table>
      </div>
    </div>

  </main>
</div>



<div class="overlay" id="borrowModal">
  <div class="modal-box">
    <h3>Borrow Book</h3>
    <p class="text-muted fst-italic mb-3" id="borrowModalTitle"></p>
    <div class="borrow-rules">
      <strong>Borrowing Rules:</strong><br>
      • Max <strong>3 books</strong> per member at a time<br>
      • Only <strong>1 copy</strong> of the same title per member<br>
      • Loan duration: <strong>1–30 days</strong><br>
      • Member must have <strong>Active</strong> status
    </div>
    <div class="mb-3">
      <label class="form-label-upper d-block mb-1">Select Member</label>
      <select id="borrowMemberSelect" class="form-select"></select>
    </div>
    <div class="mb-2">
      <label class="form-label-upper d-block mb-1">Loan Duration</label>
      <select id="borrowDuration" class="form-select">
        <option value="1">1 Day</option><option value="3">3 Days</option>
        <option value="7">7 Days</option><option value="14" selected>14 Days</option>
        <option value="21">21 Days</option><option value="30">30 Days</option>
      </select>
    </div>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeBorrowModal()">Cancel</button>
      <button class="btn-confirm" style="background:var(--forest)" onclick="confirmBorrow()">Confirm Borrow</button>
    </div>
  </div>
</div>


<div class="overlay" id="notifModal">
  <div class="modal-box">
    <h3 id="notifTitle" style="color:#8b3a1c">⚠️ Warning</h3>
    <p id="notifMessage" class="text-muted mt-2"></p>
    <div class="modal-actions">
      <button class="btn-confirm" id="notifBtn" onclick="closeNotifModal()">OK, Got it!</button>
    </div>
  </div>
</div>


<!-- <div class="overlay" id="deleteModal">
  <div class="modal-box">
    <h3>Remove Volume</h3>
    <p class="text-muted">Are you sure you wish to remove <em id="deleteModalTitle"></em> from the catalog? This cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
      <button class="btn-confirm" onclick="confirmDelete()">Remove</button>
    </div>
  </div>
</div> -->


<div class="overlay" id="deleteBookModal">
  <div class="modal-box" style="max-width:700px;width:95%">
    
    <h3>Manage Book Copies</h3>
    <p class="text-muted mb-3" id="deleteBookTitle"></p>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Copy ID</th>
            <th>Title</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="bookCopiesTable"></tbody>
      </table>
    </div>

    <div class="modal-actions mt-3">
      <button class="btn-cancel" onclick="closeDeleteBookModal()">Close</button>
    </div>

  </div>
</div>

<div class="overlay" id="addCopiesModal">
  <div class="modal-box">
    <h3>Add Copies</h3>
    <p id="addCopiesTitle" class="text-muted"></p>

    <div class="mb-3">
      <label class="form-label">Number of Copies</label>
      <input type="number" id="addCopiesInput" class="form-control" min="1" max="50" value="1">
    </div>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeAddCopiesModal()">Cancel</button>
      <button class="btn-confirm" onclick="confirmAddCopies()">Add</button>
    </div>
  </div>
</div>


<div class="overlay" id="warningModal">
  <div class="modal-box">
    <h3 style="color:#8b3a1c">⚠️ Cannot Delete Book</h3>
    <p class="text-muted mt-2"><em id="warningModalTitle"></em> cannot be deleted because it is currently borrowed. Please wait for it to be returned first.</p>
    <div class="modal-actions">
      <button class="btn-confirm" onclick="closeWarningModal()">OK, Got it!</button>
    </div>
  </div>
</div>


<div class="overlay" id="editBookModal">
  <div class="modal-box">
    <h3>Edit Book</h3>
    <div class="mb-3"><label class="form-label-upper d-block mb-1">Title</label><input type="text" id="edit-title" class="form-control"></div>
    <div class="mb-3"><label class="form-label-upper d-block mb-1">Author</label><input type="text" id="edit-author" class="form-control"></div>
    <div class="mb-3"><label class="form-label-upper d-block mb-1">ISBN</label><input type="text" id="edit-isbn" class="form-control"></div>
    <div class="mb-3">
      <label class="form-label-upper d-block mb-1">Category</label>
      <select id="edit-genre" class="form-select">
        <option>Fiction</option><option>Non-Fiction</option><option>Science</option>
        <option>History</option><option>Philosophy</option><option>Fantasy</option><option>Mystery</option>
      </select>
    </div>
    <div class="mb-2"><label class="form-label-upper d-block mb-1">Year</label><input type="number" id="edit-year" class="form-control"></div>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeEditModal()">Cancel</button>
      <button class="btn-confirm" onclick="updateBook()">Save</button>
    </div>
  </div>
</div>


<!-- <div class="overlay" id="deleteMemberModal">
  <div class="modal-box">
    <h3>Remove Member</h3>
    <p class="text-muted">Are you sure you want to remove <em id="deleteMemberName"></em>? This cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeDeleteMemberModal()">Cancel</button>
      <button class="btn-confirm" style="background:#8b3a1c" onclick="confirmDeleteMember()">Remove</button>
    </div>
  </div>
</div> -->

<div class="overlay" id="deleteMemberModal">
  <div class="modal-box">
    <h3>Remove Member</h3>
    <p class="text-muted">
      Are you sure you want to remove 
      <strong id="deleteMemberName"></strong>?
    </p>

    <div id="deleteMemberWarning" class="text-danger mb-2" style="display:none;">
      ⚠️ This member has borrowed books and cannot be removed.
    </div>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeDeleteMemberModal()">Cancel</button>
      <button class="btn-confirm" onclick="confirmRemoveMember()">Remove</button>
    </div>
  </div>
</div>


<div class="overlay" id="memberWarningModal">
  <div class="modal-box">
    <h3 style="color:#8b3a1c">⚠️ Cannot Remove Member</h3>
    <p class="text-muted mt-2"><em id="memberWarningName"></em> cannot be removed because they currently have borrowed books. Please wait for all books to be returned first.</p>
    <div class="modal-actions">
      <button class="btn-confirm" style="background:#8b3a1c" onclick="closeMemberWarningModal()">OK, Got it!</button>
    </div>
  </div>
</div>


<div class="overlay" id="editMemberModal" style="z-index:9999">
  <div class="modal-box" style="max-width:460px;padding:0;border-radius:12px;overflow:hidden">
    <div style="background:var(--dark);padding:1.2rem 1.6rem;display:flex;align-items:center;gap:.6rem">
      <span style="font-size:1.2rem">✏️</span>
      <h3 style="font-family:'Playfair Display',serif;color:var(--gold);margin:0;font-size:1.15rem">Edit Member</h3>
    </div>
    <div class="p-4 d-flex flex-column gap-3">
      <div><label class="form-label-upper d-block mb-1">Name</label><input type="text" id="edit-name" class="form-control"></div>
      <div><label class="form-label-upper d-block mb-1">Email</label><input type="text" id="edit-email" class="form-control"></div>
      <div><label class="form-label-upper d-block mb-1">Contact</label><input type="text" id="edit-contact" class="form-control"></div>
    </div>
    <div class="px-4 pb-4 d-flex justify-content-end gap-2" style="border-top:1px solid var(--border);padding-top:.9rem">
      <button onclick="closeEditMemberModal()" style="background:transparent;color:var(--text);border:1.5px solid var(--border);padding:.45rem 1.1rem;border-radius:6px;font-weight:600;font-size:.85rem;cursor:pointer;font-family:inherit">Cancel</button>
      <button onclick="updateMember()" style="background:var(--dark);color:var(--gold);border:1.5px solid var(--gold);padding:.45rem 1.2rem;border-radius:6px;font-weight:700;font-size:.85rem;cursor:pointer;font-family:inherit">💾 Save Changes</button>
    </div>
  </div>
</div>


<div class="overlay" id="memberBooksModal">
  <div class="modal-box" style="max-width:1100px;width:98%;padding:0;border-radius:12px;overflow:hidden">
    <!-- <div class="modal-box" style="max-width:860px;width:96%;padding:0;border-radius:12px;overflow:hidden"></div> -->
    <div style="background:var(--dark);padding:1.4rem 1.8rem;display:flex;align-items:center;justify-content:space-between">
      <div class="d-flex align-items-center gap-3">
        <span style="font-size:1.5rem">📚</span>
        <div>
          <h3 style="font-family:'Playfair Display',serif;color:var(--gold);margin:0;font-size:1.25rem">Member Details</h3>
          <p id="memberBooksName" style="color:rgba(255,255,255,0.55);font-size:.8rem;margin:0;font-style:italic"></p>
        </div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <span id="memberModalStatusBadge" class="status" style="font-size:.75rem;padding:.2rem .7rem"></span>
        <button onclick="openEditMemberModal()" style="background:var(--gold);color:var(--dark);border:none;padding:.45rem 1rem;border-radius:6px;font-weight:700;font-size:.8rem;cursor:pointer;font-family:inherit;letter-spacing:.05em">&#9999;&#65039; Edit</button>
      </div>
    </div>
    <div class="d-flex" style="border-bottom:2px solid var(--border)">
      <button id="modalTab-borrowed" onclick="switchMemberModalTab('borrowed')" style="flex:1;padding:.7rem;border:none;background:var(--navy-mid);color:var(--champagne);font-family:'Crimson Pro',serif;font-size:.9rem;font-weight:700;cursor:pointer;letter-spacing:.04em">&#128218; Borrowed Books</button>
      <button id="modalTab-fines" onclick="switchMemberModalTab('fines')" style="flex:1;padding:.7rem;border:none;background:#f5f0e8;color:var(--muted);font-family:'Crimson Pro',serif;font-size:.9rem;font-weight:700;cursor:pointer;letter-spacing:.04em">&#128176; Fines <span id="memberFinesBadge" style="display:none;background:#c0392b;color:#fff;border-radius:10px;padding:.05rem .4rem;font-size:.75rem;margin-left:.3rem">0</span></button>
    </div>
    <div id="memberModalPanel-borrowed" class="p-4">
      <div class="table-responsive" style="border-radius:8px;overflow:hidden;border:1px solid var(--border)">
        <!-- <table class="table table-hover align-middle mb-0"> -->
        <table class="table table-hover align-middle mb-0" style="min-width: 900px;">
          <thead><tr>
            <th style="width:120px">BookCopy_ID</th>
            <th>Book_Title</th>
            <th>Author_Name</th>
            <th>Category_Name</th>
            <th>Borrow_Date</th>
            <th>Due_Date</th>
            <th style="width:130px">Status</th>
          </tr></thead>
          <tbody id="memberBooksTable"></tbody>
        </table>
      </div>
    </div>
    <div id="memberModalPanel-fines" style="display:none" class="p-4">
      <div class="table-responsive" style="border-radius:8px;overflow:hidden;border:1px solid var(--border)">
        <table class="table table-hover align-middle mb-0">
          <thead><tr>
            <th>Fines_ID</th>
            <th>Book_Title</th>
            <th>Fine_Amount</th>
            <th>Issued_Date</th>
            <th>Paid_Date</th>
            <th>Fine_Status_Name</th>
            <th>Actions</th>
          </tr></thead>
          <tbody id="memberFinesTable"></tbody>
        </table>
      </div>
    </div>
    <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-top:1px solid var(--border)">
      <button onclick="toggleMemberStatus()" id="memberStatusToggleBtn" style="background:var(--dark);color:var(--gold);border:1.5px solid var(--gold);padding:.45rem 1.2rem;border-radius:6px;font-weight:700;font-size:.85rem;cursor:pointer;font-family:inherit">&#9889; Change Status</button>
      <button onclick="closeMemberBooksModal()" style="background:transparent;color:var(--text);border:1.5px solid var(--border);padding:.45rem 1.2rem;border-radius:6px;font-weight:600;font-size:.85rem;cursor:pointer;font-family:inherit">Close</button>
    </div>
  </div>
</div>


<div class="overlay" id="permDeleteModal">
  <div class="modal-box">
    <h3>Permanently Delete Record</h3>
    <p class="text-muted">This will permanently remove transaction <em id="permDeleteTxId"></em>. This cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closePermDeleteModal()">Cancel</button>
      <button class="btn-confirm" style="background:#8b3a1c" onclick="confirmPermDelete()">Delete Permanently</button>
    </div>
  </div>
</div>




<div class="overlay" id="fineNotifModal">
  <div class="modal-box">
    <h3 id="fineNotifTitle" style="color:#c0392b">⚠️ Overdue Fine Issued</h3>
    <p id="fineNotifMessage" class="text-muted mt-2"></p>
    <p class="mt-3" style="font-size:.85rem;color:var(--muted);font-style:italic">The fine has been added to the Fines section. You can mark it as Paid or Waived there.</p>
    <div class="modal-actions">
      <button class="btn-confirm" style="background:#c0392b" onclick="closeFineNotifModal()">OK, Got it!</button>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- <script src="Assets/script.js"></script> -->
 <script src="assets/toast.js"></script>
<script src="assets/dashboard.js"></script>
</body>
<div id="toastContainer"></div>
<!-- <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div> -->
</html> 

