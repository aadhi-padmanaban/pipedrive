
@extends('layouts.app')

@section('title', 'Customer Transactions')

@section('content')
    <h3 class="mb-3">Customer Transactions</h3>
    <!-- <form id="emailForm" class="mb-4">
        <div class="input-group">
            <input type="email" class="form-control" id="emailInput" placeholder="Enter customer email" required>
            <button class="btn btn-primary" type="submit">Fetch</button>
        </div>
    </form> -->
    <div id="transactions" class="list-group"></div>

    <script>
        async function fetchTransactions() {
            // const urlParams = new URLSearchParams(window.location.search);
            
            // const personId = urlParams.get("selectedIds");
            // const companyId = urlParams.get("companyId");

            const context = @json($context);

            // Pipedrive iframe sends context as POST JSON
            let personId = context.selectedIds || null;
            let companyId = context.companyId || null;

            const container = document.getElementById("transactions");
            container.innerHTML = `<div class="alert alert-info">Fetching data...</div>`;

            try {
                // const resp = await fetch("/api/transactions?email=" + encodeURIComponent(email));
                const resp = await fetch("/api/transactions?personId=" + encodeURIComponent(personId)+"&companyId="+encodeURIComponent(companyId));
                if (!resp.ok) {
                    const errorText = await resp.json(); // get error details
                    throw new Error(errorText.error);
                }
                const data = await resp.json();
                
                const invoices = data.transactions.invoices || [];
                const charges = data.transactions.charges || [];

                // Early exit if both are empty
                if (invoices.length === 0 && charges.length === 0) {
                    container.innerHTML = `<div class="alert alert-warning">No transactions found</div>`;
                    return;
                }

                let html = "";

                // Render Invoices
                html += `<h4>Invoices (${invoices.length})</h4>`;
                html += invoices.map(inv => `
                    <div class="list-group-item">
                        <h5>Invoice #${inv.id}</h5>
                        <p>Amount: $${inv.amount}</p>
                        <p>Status: ${inv.status}</p>
                        <p>Customer: ${inv.customer}</p>
                        <p>Date: ${inv.date}</p>
                        ${inv.receipt ? `<a target="_blank" href="${inv.receipt}" class="btn btn-sm btn-primary">${inv.status === 'paid' ? 'View Receipt' : 'View Invoice Payment page'}</a>` : ''}
                    </div>
                `).join('');

                // Render Charges
                html += `<h4 class="mt-4">Transactions (${charges.length})</h4>`;
                html += charges.map(ch => `
                    <div class="list-group-item">
                        <h5>Charge #${ch.id}</h5>
                        <p>Amount: $${ch.amount}</p>
                        <p>Status: ${ch.status}</p>
                        <p>Customer: ${ch.customer}</p>
                        <p>Date: ${ch.date}</p>
                        
                    </div>
                `).join('');

                container.innerHTML = html;
            } catch (err) {
                container.innerHTML = `<div class="alert alert-danger">Error loading data: ${err.message}</div>`;
            }
        }
        fetchTransactions();
        // document.getElementById("emailForm").addEventListener("submit", function (e) {
        //     e.preventDefault();
        //     const email = document.getElementById("emailInput").value.trim();
        //     if (email) fetchTransactions(email);
        // });
    </script>
@endsection
