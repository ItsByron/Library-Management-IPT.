// #region Global State Variables 
let books = [];
let membersActive = [];
let membersAll = [];
let borrowedList = [];
let deleteMemberId = null;
let currentMemberId = null;
let currentMemberStatus = null;
let editBookId = null;
let selectedBookId = null;
let pendingBorrowCopyId = null;
let searchTimer = null;
let historyList = [];
// #endregion

//#region Global funcstions
async function logout() {
  await fetch('../routes/loginRoute.php?action=logout');
  window.location.href = 'login.php';
}
function debounceSearch(section) {
  clearTimeout(searchTimer);

  searchTimer = setTimeout(() => {
    if (section === 'members') {
      renderMembers(); 
    }
    if (section === 'history') {
      renderHistory(); 
    }
    if (section === 'catalog') {
      loadBooks(); 
    }
  }, 300); // delay (ms)
}
function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer');

  const colors = {
    success: 'bg-success',
    error: 'bg-danger',
    warning: 'bg-warning text-dark',
    info: 'bg-primary'
  };

  const toast = document.createElement('div');


  toast.className = `custom-toast align-items-center text-white ${colors[type] || 'bg-secondary'} border-0 mb-2`;
  toast.role = 'alert';

  toast.innerHTML = `
  <div class="toast-content">
    <span class="toast-message">${message}</span>
    <button type="button" class="toast-close">&times;</button>
  </div>
`;

  container.appendChild(toast);


  setTimeout(() => {
  toast.classList.add('show');
}, 50);

toast.querySelector('.toast-close').onclick = () => hideToast(toast);

  // auto hide
  setTimeout(() => {
    hideToast(toast);
  }, 3000);
}
function hideToast(toast) {
  toast.classList.remove('show');
  toast.classList.add('hide');

  setTimeout(() => {
    toast.remove();
  }, 400);
}
function showSection(id, btn) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));
  document.getElementById('section-' + id).classList.add('active');
  if (btn) btn.classList.add('active');

  if (id === 'catalog')  { loadBooks(); loadActiveMembers();}
  if (id === 'members')  { loadAllMembers(); }
  if (id === 'borrowed') { loadBorrowed(); }
  if (id === 'history')  { loadTransactions(); }
}
//#endregion

//#region Catalog
async function loadBooks() {
  try {
    console.log("loadBooks called");


    const search = (document.getElementById('searchInput')?.value || '').toLowerCase();

    const response = await fetch(`../routes/booksRoute.php?action=getAllBooks`);
    const result = await response.json();

    books = result.data || [];
    console.log("RESULT:", result);

    const catalog = document.getElementById('bookGrid');
    const countEl = document.getElementById('bookCount');

    if (!catalog) return;

    if (result.status === 'error') {
      catalog.innerHTML = `<p class="text-danger">${result.message}</p>`;
      return;
    }

    const filtered = books.filter(book => {

      const matchSearch =
        !search ||
        (book.Book_Title && book.Book_Title.toLowerCase().includes(search)) ||
        (book.Author_Name && book.Author_Name.toLowerCase().includes(search)) ||
        (book.Category_Name && book.Category_Name.toLowerCase().includes(search)) ||
        (book.Isbn && book.Isbn.toLowerCase().includes(search));

      return matchSearch;
    });

    if (!filtered.length) {
      catalog.innerHTML = `<p class="text-muted">No books found.</p>`;
      if (countEl) countEl.textContent = "(0 books)";
      return;
    }

    if (countEl) {
      countEl.textContent = `(${filtered.length} books)`;
    }

    const COLORS = {
      Fiction: ['#1e3a8a', '#fff'],
      "Non-Fiction": ['#065f46', '#fff'],
      Science: ['#7c2d12', '#fff'],
      History: ['#4c1d95', '#fff'],
      Default: ['#374151', '#fff']
    };


    catalog.innerHTML = filtered.map(book => {

      const colorKey = book.Category_Name || 'Default';
      const [bg, fg] = COLORS[colorKey] || COLORS.Default;

      const available = parseInt(book.Available_Copies || 0);
      const borrowed = available === 0;

      const badge = borrowed
        ? `<span class="borrowed-badge">Out of Stock</span>`
        : `<span class="available-badge">Available</span>`;

      const borrowBtn = borrowed
        ? `<button class="btn btn-borrow disabled" disabled>Unavailable</button>`
        : `<button class="btn btn-borrow" onclick="openBorrowModal(${book.Book_ID})">Borrow</button>`;

      return `
        <div class="col-md-3 mb-3">
          <div class="book-card small-card">

            <div class="book-body">

              <div class="book-cover small-cover" style="background:${bg};color:${fg}">
                ${book.Book_Title}
              </div>

              <div class="book-title">
                ${book.Book_Title}
                ${book.Isbn ? `<span class="book-isbn">(${book.Isbn})</span>` : ''}
              </div>

              <div class="book-author">${book.Author_Name}</div>

              <span class="book-genre" style="background:${bg}22;color:${bg}">
                ${book.Category_Name}
              </span>

              <div class="copies-text">
                ${available} / ${book.Total_Copies} copies available
              </div>

              <div style="margin-bottom:.4rem">
                ${badge}
              </div>

              <div class="book-actions">
                ${borrowBtn}
                <button class="btn btn-edit" onclick="openEditModal(${book.Book_ID})">Edit</button>
                <button class="btn btn-del" onclick="deleteBook(${book.Book_ID})">Del</button>
                <button class="btn btn-add-copy" onclick="openAddCopiesModal(${book.Book_ID})">Add Copy</button>
              </div>

            </div>
          </div>
        </div>
      `;
    }).join('');

  } catch (err) {
    console.error("LOAD BOOKS ERROR:", err);
  }
}



document.addEventListener("DOMContentLoaded", () => {


   const message = localStorage.getItem('toastMessage');
  const type = localStorage.getItem('toastType');

  if (message) {
    showToast(message, type);


    localStorage.removeItem('toastMessage');
    localStorage.removeItem('toastType');
  }

  loadBooks();
  loadActiveMembers();

});
//#endregion

//#region Members functions
async function loadActiveMembers() {
  try {
    console.log("Members CLick");
    const res = await fetch('../routes/membersRoute.php?action=getActiveMembers');
    const result = await res.json();
    console.log("ACTIVE MEMBERS RESULT:", result);
    if (result.status === 'success') {
      membersActive = result.data; 
    } else {
      console.error("Failed to load members");
    }

  } catch (err) {
    console.error("MEMBERS ERROR:", err);
  }
}
async function loadAllMembers() {
  try {
    console.log("Members Clicked");

    const res = await fetch('../routes/membersRoute.php?action=getAllMembers');
    const result = await res.json();

    if (result.status === 'success') {
      membersAll = result.data;

      renderMembers(); 

    } else {
      console.error("Failed to load members");
    }

  } catch (err) {
    console.error("MEMBERS ERROR:", err);
  }
}
function renderMembers() {
  const booksFilter = document.getElementById('memberBooksFilter').value;
  const search = document.getElementById('memberSearch').value.toLowerCase();

  let filtered = membersAll.filter(m => {

    const booksOut = parseInt(m.Books_Out || 0); 

    const matchSearch =
      !search ||
      (m.Member_Name && m.Member_Name.toLowerCase().includes(search)) ||
      (m.Email && m.Email.toLowerCase().includes(search)) ||
      (m.Contact_Number && m.Contact_Number.toLowerCase().includes(search));

    const matchFilter =
      !booksFilter ||
      (booksFilter === 'active' && booksOut > 0) ||
      (booksFilter === 'clear' && booksOut === 0);

    return matchSearch && matchFilter;
  });

  const tbody = document.getElementById('memberTable');

  if (!filtered.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align:center;padding:2rem;color:var(--muted);font-style:italic">
          No members found.
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(m => {

    const booksOut = parseInt(m.Books_Out || 0); 

    const status = m.Member_Status_Name || 'Unknown';
    const statusCls = status.toLowerCase();

    return `
      <tr>
        <td style="font-weight:600">${m.Member_Name}</td>

        <td>
          <span class="id-chip">
            MBR-${String(m.Member_ID).padStart(4,'0')}
          </span>
        </td>

        <td style="color:var(--muted);font-style:italic">
          ${m.Email || '—'}
        </td>

        <td>${m.Contact_Number || '—'}</td>

        <td>${m.Date_Joined || '—'}</td>

        <td style="text-align:center">
          <span style="font-weight:600;color:${booksOut > 0 ? 'var(--rust)' : 'var(--forest)'}">
            ${booksOut}
          </span>
        </td>

        <td style="text-align:center">
          <span class="status ${statusCls}">
            ${status}
          </span>
        </td>

        <td>
          <div style="display:flex;gap:.4rem">
            <button class="btn-action btn-edit" onclick="openMemberBooksModal(${m.Member_ID})">
              View
            </button>
            <button class="btn-action btn-del" onclick="removeMember(${m.Member_ID})">
              Remove
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}
async function openMemberBooksModal(id) {
  currentMemberId = id;


  const m = membersAll.find(x => x.Member_ID == id);
  currentMemberStatus = parseInt(m?.Member_Status_ID) || 1;

  document.getElementById('memberBooksName').textContent =
    m ? `Showing borrowed books for: ${m.Member_Name}` : '';


  const badge = document.getElementById('memberModalStatusBadge');
  if (m) {
    badge.textContent = m.Member_Status_Name;
    badge.className = `status ${m.Member_Status_Name.toLowerCase()}`;
    badge.style.display = 'inline-block';
  }


  document.getElementById('memberBooksModal').classList.add('open');
  switchMemberModalTab('borrowed');

  const tbody = document.getElementById('memberBooksTable');

  // loading
  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--muted);font-style:italic">
        ⏳ Loading...
      </td>
    </tr>`;

  const today = new Date().toISOString().split('T')[0];
  let hasOverdue = false;

  try {

    const res = await fetch(`../routes/membersRoute.php?action=getMemberBorrowed&Member_ID=${id}`);
    const result = await res.json();

    if (result.status !== 'success' || !result.data.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--muted);font-style:italic">
            📭 No currently borrowed books.
          </td>
        </tr>`;
    } else {

      tbody.innerHTML = result.data.map(b => {

        const borrow = b.Borrow_Date || '—';
        const due = b.Due_Date || '—';

        const overdue = ((b.Borrow_Status_ID == 1 || b.Borrow_Status_ID == 3)  && due < today);

        if (overdue) hasOverdue = true;

        return `
          <tr>
            <td>
              <span class="id-chip">
                CPY-${String(b.BookCopy_ID).padStart(4,'0')}
              </span>
            </td>

            <td style="font-weight:600">${b.Book_Title || '—'}</td>

            <td style="color:var(--muted);font-style:italic">
              ${b.Author_Name || '—'}
            </td>

            <td>${b.Category_Name || '—'}</td>

            <td>${borrow}</td>

            <td style="color:${overdue ? '#c0392b' : 'inherit'};font-weight:${overdue ? '600' : '400'}">
              ${due} ${overdue ? '⚠️' : ''}
            </td>

            <td>
              <span class="status-badge ${overdue ? 'status-overdue' : 'status-borrowed'}">
                ${overdue ? 'Overdue' : 'Borrowed'}
              </span>
            </td>
          </tr>
        `;
      }).join('');
    }


    if (hasOverdue && currentMemberStatus == 1) {

      await fetch(`../routes/membersRoute.php?action=toggleStatus`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          Member_ID: id,
          Member_Status_ID: 2
        })
      });

      await loadAllMembers();

      badge.textContent = 'Restricted';
      badge.className = 'status restricted';
    }

  } catch (e) {
    console.error("FETCH BORROW ERROR:", e);
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:2rem;color:#c0392b;font-weight:600">
          ⚠️ Could not load borrowed books.
        </td>
      </tr>`;
  }


  const finesTbody = document.getElementById('memberFinesTable');
  const badgeEl = document.getElementById('memberFinesBadge');

  finesTbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align:center;padding:2rem;color:var(--muted);font-style:italic">
        ⏳ Loading fines...
      </td>
    </tr>`;

  try {


    const res = await fetch(`../routes/membersRoute.php?action=getMemberFines&Member_ID=${id}`);
    const result = await res.json();

    if (result.status !== 'success' || !result.data.length) {

      finesTbody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--muted);font-style:italic">
            🎉 No fines recorded.
          </td>
        </tr>`;

      badgeEl.style.display = 'none';
      return;
    }

    const fines = result.data;


    const unpaid = fines.filter(f => f.Fine_Status_ID == 1).length;

    if (unpaid > 0) {
      badgeEl.textContent = unpaid;
      badgeEl.style.display = 'inline-block';
    } else {
      badgeEl.style.display = 'none';
    }

    finesTbody.innerHTML = fines.map(f => {

      const issued = f.Issued_Date || '—';
      const paid = f.Paid_Date || '—';
      const isPaid = f.Fine_Status_ID == 2;

      let actions = '';

      if (!isPaid) {
        actions = `
          <button class="btn-action btn-pay" onclick="payFine(${f.Fines_ID})">Pay</button>
        `;
      } else {
        actions = `<span style="font-size:.8rem;color:var(--muted)">Paid</span>`;
      }

      return `
        <tr>
          <td class="id-chip">FINE-${String(f.Fines_ID).padStart(4,'0')}</td>
          <td>${f.Book_Title || '—'}</td>
          <td>₱${parseFloat(f.Fine_Amount).toFixed(2)}</td>
          <td>${issued}</td>
          <td>${paid}</td>
          <td>
            <span class="status-badge ${isPaid ? 'status-paid' : 'status-unpaid'}">
              ${isPaid ? 'Paid' : 'Unpaid'}
            </span>
          </td>
          <td>${actions}</td>
        </tr>
      `;
    }).join('');

  } catch (e) {
    console.error("FETCH FINES ERROR:", e);

    finesTbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:2rem;color:#c0392b">
          ⚠️ Could not load fines.
        </td>
      </tr>`;
  }
}
async function payFine(fineId) {
  try {

    const res = await fetch(`../routes/fineRoute.php?action=payFine`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ Fines_ID: fineId })
    });


    const text = await res.text();
    console.log("RAW RESPONSE:", text);

    let result;

    try {
      result = JSON.parse(text);
    } catch (err) {
      console.error("INVALID JSON:", text);
      throw new Error("Invalid JSON from server");
    }

    console.log("PAY RESULT:", result);

    if (result.status === 'success') {
      showToast(result.message || "Fine paid successfully", "success");
      loadAllMembers();

      await openMemberBooksModal(currentMemberId);

    } else {
      showToast(result.message || "Failed to pay fine", "error");
    }

  } catch (e) {
    console.error("PAY FINE ERROR:", e);
    showToast("Something went wrong", "error");
  }
}
function switchMemberModalTab(tab) {
  const isBorrowed = tab === 'borrowed';
  document.getElementById('memberModalPanel-borrowed').style.display = isBorrowed ? 'block' : 'none';
  document.getElementById('memberModalPanel-fines').style.display    = isBorrowed ? 'none' : 'block';
  document.getElementById('modalTab-borrowed').style.background = isBorrowed ? 'var(--navy-mid)' : '#f5f0e8';
  document.getElementById('modalTab-borrowed').style.color      = isBorrowed ? 'var(--champagne)' : 'var(--muted)';
  document.getElementById('modalTab-fines').style.background    = isBorrowed ? '#f5f0e8' : 'var(--navy-mid)';
  document.getElementById('modalTab-fines').style.color         = isBorrowed ? 'var(--muted)' : 'var(--champagne)';
}
function closeMemberBooksModal() {
  const modal = document.getElementById('memberBooksModal');

  if (modal) {
    modal.classList.remove('open');
  }


  currentMemberId = null;


  document.getElementById('memberBooksTable').innerHTML = '';
  document.getElementById('memberFinesTable').innerHTML = '';


  document.getElementById('memberBooksName').textContent = '';
}
async function toggleMemberStatus() {

  if (!currentMemberId) return;

  const newStatusId = Number(currentMemberStatus) === 1 ? 2 : 1;

  console.log("OLD:", currentMemberStatus, "NEW:", newStatusId);

  try {
    console.log("Sending Member_ID:", currentMemberId);
    const res = await fetch(`../routes/membersRoute.php?action=toggleStatus`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        Member_ID: currentMemberId,
        Member_Status_ID: newStatusId
      })
    });

    const result = await res.json();
    console.log("STATUS RESULT:", result);

    showToast(result.message, result.status);

    if (result.status === 'success') {

      currentMemberStatus = newStatusId; 

      document.getElementById('memberModalStatusBadge').textContent =
        newStatusId === 1 ? 'Active' : 'Restricted';

      closeMemberBooksModal();
      await loadAllMembers();
    }

  } catch (e) {
    console.error("STATUS ERROR:", e);
    showToast("Something went wrong", "error");
  }
}
async function addMember() {
  const name    = document.getElementById('m-name').value.trim();
  const email   = document.getElementById('m-email').value.trim();
  const contact = document.getElementById('m-contact').value.trim();


  if (!name) {
    showToast("Name is required!", "error");
    return;
  }

  if (!email && !contact) {
    showToast("Provide at least Email or Contact Number!", "warning");
    return;
  }

  try {
    const res = await fetch('../routes/membersRoute.php?action=addMember', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        Member_Name: name,
        Email: email,
        Contact_Number: contact
      })
    });

    const result = await res.json();
    console.log("ADD MEMBER RESULT:", result);


    showToast(
      result.message || (result.status === 'success' ? "Member added!" : "Error"),
      result.status === 'success' ? 'success' : 'error'
    );

    if (result.status === 'success') {
      // reset form
      document.getElementById('m-name').value = '';
      document.getElementById('m-email').value = '';
      document.getElementById('m-contact').value = '';

      // reload members
      if (typeof loadAllMembers === "function") await loadAllMembers();
    }

  } catch (e) {
    console.error("ADD MEMBER ERROR:", e);
    showToast("Something went wrong!", "error");
  }
}
function openEditMemberModal() {

  const m = membersAll.find(x => x.Member_ID == currentMemberId);

  if (!m) {
    console.error("Member not found");
    return;
  }


  document.getElementById('edit-name').value    = m.Member_Name || '';
  document.getElementById('edit-email').value   = m.Email || '';
  document.getElementById('edit-contact').value = m.Contact_Number || '';


  document.getElementById('editMemberModal').classList.add('open');
}
async function updateMember() {

  const name    = document.getElementById('edit-name').value.trim();
  const email   = document.getElementById('edit-email').value.trim();
  const contact = document.getElementById('edit-contact').value.trim();


  if (!name) {
    showToast("Name is required", "error");
    return;
  }

  if (!email && !contact) {
    showToast("Provide at least Email or Contact Number", "error");
    return;
  }

  try {
    const res = await fetch(`../routes/membersRoute.php?action=updateMember`, {
      method: 'POST', 
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        Member_ID: currentMemberId,
        Member_Name: name,
        Email: email,
        Contact_Number: contact
      })
    });


    const text = await res.text();
    console.log("RAW UPDATE:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (err) {
      console.error("INVALID JSON:", text);
      throw new Error("Invalid JSON from server");
    }

    console.log("UPDATE RESULT:", result);

    if (result.status === 'success') {

      showToast(result.message || "Member updated successfully", "success");


      closeEditMemberModal();


      await loadAllMembers();
      if (typeof loadBorrowed === "function") await loadBorrowed();

    } else {
      showToast(result.message || "Update failed", "error");
    }

  } catch (e) {
    console.error("UPDATE ERROR:", e);
    showToast("Something went wrong", "error");
  }
}
function closeEditMemberModal() {
  document.getElementById('editMemberModal').classList.remove('open');

  document.getElementById('edit-name').value = '';
  document.getElementById('edit-email').value = '';
  document.getElementById('edit-contact').value = '';
}
function removeMember(memberId) {
  deleteMemberId = memberId;

  const member = membersAll.find(m => m.Member_ID == memberId);

  // set name in modal
  document.getElementById('deleteMemberName').textContent =
    member ? member.Member_Name : '';


  const booksOut = borrowedList.filter(
    t => t.Member_ID == memberId && t.Borrow_Status_ID != 2
  ).length;

  const warning = document.getElementById('deleteMemberWarning');
  const confirmBtn = document.querySelector('#deleteMemberModal .btn-confirm');

  if (booksOut > 0) {
    warning.style.display = 'block';
    confirmBtn.disabled = true; 
  } else {
    warning.style.display = 'none';
    confirmBtn.disabled = false; 
  }

  document.getElementById('deleteMemberModal').classList.add('open');
}
function closeDeleteMemberModal() {
  document.getElementById('deleteMemberModal').classList.remove('open');
  deleteMemberId = null;
}
async function confirmRemoveMember() {
  if (!deleteMemberId) return;

  try {
    const res = await fetch('../routes/membersRoute.php?action=deleteMember', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ Member_ID: deleteMemberId })
    });

    const result = await res.json();

    showToast(
      result.message,
      result.status === 'success' ? 'success' : 'error'
    );

    if (result.status === 'success') {
      closeDeleteMemberModal();
      await loadAllMembers();
    }

  } catch (err) {
    console.error(err);
    showToast("Something went wrong", "error");
  }
}
//#endregion

//#region Borrow functions
async function loadBorrowed() {
  try {
    console.log("Loading borrowed...");

    const res = await fetch('../routes/booksRoute.php?action=getBorrowed');
    

    const text = await res.text();
    console.log("RAW BORROWED:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (err) {
      console.error("INVALID JSON (borrowed):", text);
      throw new Error("Invalid JSON from server");
    }

    if (result.status === 'success') {

      borrowedList = result.data || [];

      console.log("BORROWED LIST:", borrowedList);


      if (typeof mergeBorrowedStatus === "function") mergeBorrowedStatus();
      if (typeof renderCatalog === "function") renderCatalog();
      if (typeof renderBorrowed === "function") renderBorrowed();
      if (typeof updateStats === "function") updateStats();

    } else {
      console.error("Failed to load borrowed:", result.message);
    }

  } catch (e) {
    console.error("loadBorrowed ERROR:", e);
  }
}
function renderBorrowed() {

  const q = (document.getElementById('borrowedSearch').value || '').toLowerCase();
  const filter = document.getElementById('borrowedStatusFilter').value;
  const today = new Date().toISOString().split('T')[0];

  const tbody = document.getElementById('borrowTable');

  let filtered = borrowedList.filter(t => {

    const overdue = t.Due_Date && t.Due_Date < today;

    const matchQ =
      !q ||
      (t.Book_Title && t.Book_Title.toLowerCase().includes(q)) ||
      (t.Author_Name && t.Author_Name.toLowerCase().includes(q)) ||
      (t.Member_Name && t.Member_Name.toLowerCase().includes(q));

    const matchF =
      !filter ||
      (filter === 'overdue' && overdue) ||
      (filter === 'current' && !overdue);

    return matchQ && matchF;
  });

  if (!filtered.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align:center;padding:2rem;color:var(--muted);font-style:italic">
          No borrowed records found.
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(t => {

    const overdue = t.Due_Date && t.Due_Date < today;

    return `
      <tr>


        <td>${t.Borrow_ID}</td>


        <td>
          <span class="id-chip">
            CPY-${String(t.BookCopy_ID).padStart(4,'0')}
          </span>
        </td>

  
        <td style="font-weight:600">
          ${t.Book_Title}
        </td>


        <td style="color:var(--muted);font-style:italic">
          ${t.Author_Name}
        </td>

        <td>${t.Member_ID}</td>


        <td>${t.Member_Name}</td>


        <td>${t.Borrow_Date}</td>


        <td style="color:${overdue ? '#c0392b' : 'inherit'};font-weight:${overdue ? '600' : '400'}">
          ${t.Due_Date} ${overdue ? '⚠️' : ''}
        </td>


        <td>
          <button class="btn-action btn-return" onclick="returnBook(${t.BorrowDetails_ID})">
            Return
          </button>
        </td>

      </tr>
    `;
  }).join('');
}
async function returnBook(borrowDetailsId) {
  try {

    const res = await fetch(`../routes/booksRoute.php?action=returnBook`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        BorrowDetails_ID: borrowDetailsId
      })
    });

    const text = await res.text();
    console.log("RAW RETURN:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (err) {
      console.error("INVALID JSON:", text);
      throw new Error("Invalid JSON response");
    }

    console.log("RETURN RESULT:", result);

    if (result.status === 'success') {


      showToast(result.message || "Book returned successfully", "success");


      await loadBooks();
      await loadBorrowed();
      if (typeof loadTransactions === "function") await loadTransactions();
      if (typeof loadAllMembers === "function") await loadAllMembers();

    } else {
      showToast(result.message || "Return failed", "error");
    }

  } catch (e) {
    console.error("RETURN ERROR:", e);
    showToast("Something went wrong", "error");
  }
}
function openBorrowModal(bookId) {
  pendingBorrowCopyId = bookId;


  const b = books.find(x => x.Book_ID == bookId);


  document.getElementById('borrowModalTitle').textContent =
    b ? `"${b.Book_Title}"` : '';


  const select = document.getElementById('borrowMemberSelect');
  const active = membersActive;

  select.innerHTML = active.length
    ? active.map(m => `
        <option value="${m.Member_ID}">
          ${m.Member_Name}
        </option>
      `).join('')
    : '<option value="">No active members available</option>';


  document.getElementById('borrowModal').classList.add('open');
}
async function confirmBorrow() {
  const memberId = parseInt(document.getElementById('borrowMemberSelect').value);
  const duration = parseInt(document.getElementById('borrowDuration').value);


  if (!memberId) {
    alert("Please select a member!");
    return;
  }

  if (!pendingBorrowCopyId) {
    showToast("No book selected!","error");
    // alert("No book selected!");
    return;
  }

  if (!duration || duration <= 0) {
    alert("Invalid duration!");
    return;
  }


  const today = new Date();
  const due   = new Date(today);
  due.setDate(due.getDate() + duration);

  // const fmt = d => d.toISOString().split('T')[0];
  const fmt = d => {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

  try {
    const res = await fetch('../routes/booksRoute.php?action=borrowBook', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        Book_ID: pendingBorrowCopyId,
        Member_ID: memberId,
        Borrow_Date: fmt(today),
        Due_Date: fmt(due),

      })
    });


    if (!res.ok) {
      throw new Error("Server error: " + res.status);
    }

    const result = await res.json();
    console.log("BORROW RESULT:", result);

    if (result.status === 'success') {
      showToast("Book borrowed successfully!", "success");
      // alert(result.message || "Book borrowed successfully!");

      closeBorrowModal();


      await loadBooks();
      if (typeof loadBorrowed === "function") await loadBorrowed();
      if (typeof loadTransactions === "function") await loadTransactions();

    } else {
      showToast("Borrow failed", "error");
      // alert(result.message || "Borrow failed");
    }

  } catch (err) {
    console.error("BORROW ERROR:", err);
    alert("Something went wrong while borrowing");
  }
}
function closeBorrowModal() {
  document.getElementById('borrowModal').classList.remove('open');

  // optional: reset values
  document.getElementById('borrowMemberSelect').value = '';
  document.getElementById('borrowDuration').value = '14';

  // clear pending ID
  pendingBorrowCopyId = null;
}
//#endregion

//#region Books functions
async function addBook() {
  const title   = document.getElementById('f-title').value.trim();
  const author  = document.getElementById('f-author').value.trim();
  const isbn    = document.getElementById('f-isbn').value.trim();
  const genre   = document.getElementById('f-genre').value;
  const year    = document.getElementById('f-year').value || null;
  const copies  = parseInt(document.getElementById('f-copies').value) || 0;


  if (!title || !author || !isbn || !genre || !copies) {
    showToast("All fields are required!", "error");
    return;
  }

  if (copies <= 0) {
    showToast("Copies must be at least 1", "warning");
    return;
  }

  try {
    const res = await fetch('../routes/booksRoute.php?action=addBook', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        Title: title,
        Author: author,
        ISBN: isbn,
        Genre: genre,
        Year: year,
        CopyCount: copies
      })
    });

    const result = await res.json();
    console.log("ADD BOOK RESULT:", result);

    showToast(result.message, result.status);

    if (result.status === 'success') {

      ['f-title','f-author','f-isbn','f-year'].forEach(id => {
        document.getElementById(id).value = '';
      });
      document.getElementById('f-copies').value = 1;


      await loadBooks();
    }

  } catch (e) {
    console.error("ADD BOOK ERROR:", e);
    showToast("Something went wrong!", "error");
  }
}
function openEditModal(id) {
  editBookId = id;


  const b = books.find(x => x.Book_ID == id);
  if (!b) return;


  document.getElementById('edit-title').value  = b.Book_Title || '';
  document.getElementById('edit-author').value = b.Author_Name || '';
  document.getElementById('edit-genre').value  = b.Category_Name || 'Fiction';
  document.getElementById('edit-year').value   = b.Publication_Year || '';

  // optional (if you actually have ISBN)
  if (document.getElementById('edit-isbn')) {
    document.getElementById('edit-isbn').value = b.Isbn || '';
  }


  document.getElementById('editBookModal').classList.add('open');
}
// function closeEditModal()    { document.getElementById('editBookModal').classList.remove('open'); }
function closeEditModal() {
  const modal = document.getElementById('editBookModal');

  if (modal) {
    modal.classList.remove('open');
  }

  // optional: reset fields
  document.getElementById('edit-title').value = '';
  document.getElementById('edit-author').value = '';
  document.getElementById('edit-genre').value = 'Fiction';
  document.getElementById('edit-year').value = '';

  // reset ID
  editBookId = null;
}
async function updateBook() {
  try {

    const payload = {
      Book_ID: editBookId,
      Title: document.getElementById('edit-title').value.trim(),
      Author: document.getElementById('edit-author').value.trim(),
      ISBN: document.getElementById('edit-isbn')?.value.trim() || '',
      Genre: document.getElementById('edit-genre').value,
      Year: document.getElementById('edit-year').value || null
    };

    console.log("UPDATE PAYLOAD:", payload); 

    const res = await fetch('../routes/booksRoute.php?action=updateBook', {
      method: 'POST', // PHP usually uses POST instead of PUT
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await res.json();
    console.log("UPDATE RESULT:", result);

    closeEditModal();


     showToast(
      result.message || (result.status === 'success' ? "Updated!" : "Update failed"),
      result.status === 'success' ? 'success' : 'error'
    );
    // alert(result.message || (result.status === 'success' ? "Updated!" : "Error"));


    if (result.status === 'success') {
      await loadBooks();
      if (typeof loadBorrowed === "function") await loadBorrowed();
    }

  } catch (e) {
    console.error("UPDATE ERROR:", e);
    // alert("Something went wrong while updating");
    showToast("Something went wrong while updating", "error");
  }
}
async function deleteBook(bookId) {
  try {
    const res = await fetch(`../routes/booksRoute.php?action=getBookCopies&Book_ID=${bookId}`);
    const result = await res.json();

    if (result.status !== 'success') {
      alert(result.message);
      return;
    }

    const copies = result.data;

    // set title
    const b = books.find(x => x.Book_ID == bookId);
    document.getElementById('deleteBookTitle').textContent =
      b ? `"${b.Book_Title}" Copies` : '';

    const table = document.getElementById('bookCopiesTable');

    table.innerHTML = copies.length
      ? copies.map((c, i) => {
          const status = c.Book_Status_ID == 1
            ? `<span class="badge bg-success">Available</span>`
            : `<span class="badge bg-danger">Borrowed</span>`;

          const disableDelete = c.Book_Status_ID != 1 ? 'disabled' : '';

          return `
            <tr>
              <td>${c.BookCopy_ID}</td>
              <td>${b.Book_Title}</td>
              <td>${status}</td>
              <td>
                <button class="btn btn-sm btn-danger"
                  ${disableDelete}
                  onclick="confirmDeleteCopy(${c.BookCopy_ID})">
                  Delete
                </button>
              </td>
            </tr>
          `;
        }).join('')
      : `<tr><td colspan="4" class="text-center">No copies found</td></tr>`;

    document.getElementById('deleteBookModal').classList.add('open');

  } catch (err) {
    console.error(err);
    alert("Failed to load copies");
  }
}
async function confirmDeleteCopy(copyId) {
  if (!confirm("Delete this copy?")) return;

  try {
    const res = await fetch('../routes/booksRoute.php?action=deleteCopy', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ BookCopy_ID: copyId })
    });

    const result = await res.json();

    alert(result.message);

    if (result.status === 'success') {
      // reload modal + books
      showToast("Book Deleted","success");
      closeDeleteBookModal();
      loadBooks();
    }

  } catch (err) {
    console.error(err);
    alert("Delete failed");
  }
}
function closeDeleteBookModal() {
  document.getElementById('deleteBookModal').classList.remove('open');
}
function openAddCopiesModal(bookId) {
  selectedBookId = bookId;

  const b = books.find(x => x.Book_ID == bookId);

  document.getElementById('addCopiesTitle').textContent =
    b ? `"${b.Book_Title}"` : '';

  document.getElementById('addCopiesInput').value = 1;

  document.getElementById('addCopiesModal').classList.add('open');
}
function closeAddCopiesModal() {
  document.getElementById('addCopiesModal').classList.remove('open');
  selectedBookId = null;
}
async function confirmAddCopies() {
  const count = parseInt(document.getElementById('addCopiesInput').value);

  if (!count || count <= 0) {
    showToast("Inavlid number of copies","error");
    // alert("Invalid number of copies");
    return;
  }

  try {
    const res = await fetch('../routes/booksRoute.php?action=addCopies', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        Book_ID: selectedBookId,
        Copies: count
      })
    });

    const result = await res.json();

    // alert(result.message);

    if (result.status === 'success') {
      showToast("Adding copies success", "success");
      closeAddCopiesModal();
      loadBooks(); // refresh UI
    }

  } catch (err) {
    console.error(err);
    alert("Failed to add copies");
  }
}
//#endregion

//#region Transaction functions
async function loadTransactions() {
  try {

    const res = await fetch('../routes/booksRoute.php?action=getTransactions');

    const text = await res.text();
    console.log("RAW HISTORY:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (err) {
      console.error("INVALID JSON:", text);
      throw new Error("Invalid JSON");
    }

    if (result.status === 'success') {


      historyList = Array.isArray(result.data) ? result.data : [];

      console.log("HISTORY ARRAY:", historyList);

      renderHistory();

    } else {
      console.error("Failed to load history:", result.message);
      history = [];
      renderHistory();
    }

  } catch (e) {
    console.error("loadTransactions ERROR:", e);
    history = [];
    renderHistory();
  }
}
//#endregion

//#region History functions
function renderHistory() {

  const tbody = document.getElementById('historyTable');
  const search = (document.getElementById('historySearch').value || '').toLowerCase();
  const typeFilter = document.getElementById('historyTypeFilter').value; // ✅ ADD THIS

  if (!Array.isArray(historyList)) {
    console.error("historyList is not array:", historyList);
    historyList = [];
  }

  if (!tbody) return;

  let filtered = historyList.filter(t => {

    // 🔍 SEARCH
    const matchSearch =
      !search ||
      (t.Book_Title && t.Book_Title.toLowerCase().includes(search)) ||
      (t.Member_Name && t.Member_Name.toLowerCase().includes(search)) ||
      (String(t.BorrowDetails_ID).includes(search)) ||
      (String(t.Member_ID).includes(search));

    // 📊 TYPE FILTER (THIS WAS MISSING)
    const matchFilter =
      !typeFilter ||
      (typeFilter === 'borrowed' && t.Borrow_Status_ID == 1) ||
      (typeFilter === 'returned' && t.Borrow_Status_ID == 2);

    return matchSearch && matchFilter;
  });


  if (!filtered.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="10" style="text-align:center;padding:2rem;color:var(--muted);font-style:italic">
          No history found.
        </td>
      </tr>`;
    return;
  }


  tbody.innerHTML = filtered.map(t => {

    const isBorrowed = t.Borrow_Status_ID == 1;

    return `
      <tr>
        <td>${t.BorrowDetails_ID}</td>

        <td>${t.Borrow_ID}</td>

        <td>
          <span class="id-chip">
            CPY-${String(t.BookCopy_ID).padStart(4,'0')}
          </span>
        </td>

        <td style="font-weight:600">
          ${t.Book_Title || '—'}
        </td>

        <td>${t.Member_ID}</td>

        <td>${t.Member_Name}</td>

        <td>${t.Borrow_Date || '—'}</td>

        <td>${t.Return_Date || '—'}</td>

        <td>
          <span class="status ${t.Borrow_Status_Name.toLowerCase()}">
            ${t.Borrow_Status_Name}
          </span>
        </td>

        <td>
          ${
            isBorrowed
              ? `<span style="color:gray;font-size:.8rem">Not allowed</span>`
              : `<button class="btn-action btn-del" onclick="deleteHistory(${t.BorrowDetails_ID})">
                   Delete
                 </button>`
          }
        </td>
      </tr>
    `;
  }).join('');
}
async function deleteHistory(borrowDetailsId) {

  if (!confirm("Are you sure you want to delete this record?")) return;

  try {

    const res = await fetch(`../routes/booksRoute.php?action=deleteHistory`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ BorrowDetails_ID: borrowDetailsId })
    });

    const text = await res.text();
    console.log("RAW DELETE:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (err) {
      console.error("INVALID JSON:", text);
      throw new Error("Invalid JSON");
    }

    if (result.status === 'success') {
      showToast(result.message || "Deleted successfully", "success");

      await loadTransactions();
      await loadBorrowed();
      await loadAllMembers();

    } else {
      showToast(result.message || "Delete failed", "error");
    }

  } catch (e) {
    console.error("DELETE ERROR:", e);
    showToast("Something went wrong", "error");
  }
}
//#endregion