{{-- Permission Guide — collapsible reference explaining every permission group --}}
<div class="card mb-3" id="permission-guide">
    <div class="card-header d-flex align-items-center justify-content-between" style="cursor:pointer;"
         onclick="document.getElementById('perm-guide-body').classList.toggle('d-none');">
        <h5 style="margin:0;font-size:1rem;"><i class="fas fa-circle-question" style="margin-right:6px;color:var(--lp-primary,#4f46e5);"></i>How permissions work — click to open the guide</h5>
        <i class="fas fa-chevron-down"></i>
    </div>
    <div id="perm-guide-body" class="card-body d-none" style="font-size:.87rem;line-height:1.7;">

        <p><strong>The model in one minute:</strong> a <em>role</em> is a bundle of permissions. You assign one role to each user
        (People → User List). A user can only see menus and perform actions their role allows. Roles <strong>1 (Super Admin)</strong>
        and <strong>2 (Admin/Owner)</strong> have full access regardless of what is ticked here.</p>

        <p><strong>The six columns</strong> apply to each module:</p>
        <table class="table table-sm table-bordered" style="font-size:.82rem;">
            <tr><td style="width:110px;"><strong>List</strong> (index)</td><td>See the module's main list page (e.g. all products). Without it, the other columns are useless — grant List first.</td></tr>
            <tr><td><strong>View</strong></td><td>Open a single record's detail page (e.g. one sale's invoice).</td></tr>
            <tr><td><strong>Add</strong></td><td>Create new records.</td></tr>
            <tr><td><strong>Edit</strong></td><td>Modify existing records.</td></tr>
            <tr><td><strong>Delete</strong></td><td>Remove records. Grant sparingly — deletions affect stock and reports.</td></tr>
            <tr><td><strong>Import</strong></td><td>Bulk-import via CSV. Powerful; best kept to managers.</td></tr>
        </table>

        <p><strong>Sidebar visibility toggles</strong> (bottom of this page, named "Sidebar …"): these control whether a whole
        section appears in the left menu. A user needs <em>both</em> the sidebar toggle <em>and</em> the individual permission —
        e.g. to work with sales a cashier needs <em>Sidebar Sale</em> plus <em>Sale → List/Add</em>. If a user reports
        "I can't see the menu", check the sidebar toggle first.</p>

        <p><strong>What each area covers:</strong></p>
        <ul style="padding-left:18px;">
            <li><strong>Category / Product / Purchase / Adjustment / Transfer / Stock Count</strong> — inventory management. Warehouse staff typically get Product (List), Transfer, Adjustment and Stock Count; buying staff get Purchase.</li>
            <li><strong>Sale / Quotation / Returns / Gift Card / Coupon / Delivery</strong> — the selling workflow. A cashier usually needs Sale (List, Add), Returns (Add) and nothing else; a sales manager adds Edit/Delete and Quotations.</li>
            <li><strong>Customer / Supplier / Biller / User</strong> — the People area. Note <em>User</em> permissions let someone create logins and assign roles — treat like admin power.</li>
            <li><strong>Expense / Income / Accounting / Money Transfer / Balance Sheet</strong> — finance. Usually accountant-only.</li>
            <li><strong>Employee / Attendance / Payroll / Holiday / Leave</strong> — HRM (Workforce). "Attendance" here is the <em>management</em> view of everyone's records; staff clocking themselves in/out do NOT need it (that works via their linked employee profile).</li>
            <li><strong>Reports</strong> — read-only analytics. Safe to grant broadly, but remember reports reveal costs and profit margins.</li>
            <li><strong>Settings / Warehouse / Tax / Currency / Backup / Role Permission</strong> — system administration. <em>Role Permission</em> lets a user change what everyone can do — effectively admin.</li>
            <li><strong>POS-specific</strong> — "Sale Without Stock" allows selling items with zero stock; "Today Sale/Profit" show till summaries on the POS screen.</li>
        </ul>

        <p><strong>Suggested starter roles:</strong></p>
        <ul style="padding-left:18px;">
            <li><strong>Cashier:</strong> Sidebar Sale + Sale (List, Add) + Customer (List, Add) + Today Sale. Nothing else.</li>
            <li><strong>Warehouse staff:</strong> Sidebar Product/Transfer + Product (List, View), Transfer (List, Add), Adjustment (List, Add), Stock Count.</li>
            <li><strong>Accountant:</strong> Sidebar Expense/Income/Accounting/Reports + all Finance permissions + Reports.</li>
            <li><strong>Store manager:</strong> everything except User, Role Permission, Settings and Backup.</li>
        </ul>

        <p class="text-muted" style="font-size:.8rem;"><i class="fas fa-lightbulb" style="margin-right:4px;"></i>
        Changes take effect the next time the user loads a page — they don't need to log out. Use the search box below to
        find a module quickly.</p>
    </div>
</div>
