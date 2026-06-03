@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

<header class="header">
       <nav class="headerNav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> | <a href="manual-checkin">Manual Check-In</a> | <a href="businesspoints">Business Points</a>
            <form action="{{ route('admin.logout') }}" method="POST" style="display: inline;">
               @csrf
               <button type="submit">Logout</button>
            </form>
        </nav>
</header>
<div class="container">
    @if (session('success'))
    <div style="color: green; margin-bottom: 20px; font-weight: bold;">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div style="color: red; margin-bottom: 20px; font-weight: bold;">
        {{ session('error') }}
    </div>
@endif
    <!-- Quick Stats Section -->
    <div class="quick-stats" style="display: flex; justify-content: space-around; margin: 20px 0;">
        <div class="stat-card" style="background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <h3 style="font-size: 1.5rem; color: #333; margin-bottom: 10px;">Total Customers</h3>
            <p style="font-size: 1.2rem; color: #007bff; font-weight: bold;">{{ $totalCustomers }}</p>
        </div>
        <div class="stat-card" style="background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <h3 style="font-size: 1.5rem; color: #333; margin-bottom: 10px;">Total Businesses</h3>
            <p style="font-size: 1.2rem; color: #007bff; font-weight: bold;">{{ $totalBusinesses }}</p>
        </div>
        <div class="stat-card" style="background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <h3 style="font-size: 1.5rem; color: #333; margin-bottom: 10px;">Active Users</h3>
            <p style="font-size: 1.2rem; color: #007bff; font-weight: bold;">{{ $activeUsers }}</p>
        </div>
    </div>

    <!-- Tabs for Customers and Businesses -->
    <div class="tabs" style="display: flex; justify-content: center; margin-bottom: 20px;">
        <button class="tab-button active" onclick="openTab(event, 'customers')" style="background-color: #f4f6f8; border: 1px solid #ddd; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #555; border-radius: 5px 5px 0 0; margin-right: 5px;">Customers</button>
        <button class="tab-button" onclick="openTab(event, 'businesses')" style="background-color: #f4f6f8; border: 1px solid #ddd; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #555; border-radius: 5px 5px 0 0;">Businesses</button>
    </div>

    <!-- Customers Tab Content -->
    <div id="customers" class="tab-content active">
        <h2>Customer Listings</h2>
        <div class="search-bar" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <input type="text" id="customerSearch" placeholder="Search customers..." style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 70%;">
            <button onclick="filterTable('customerTable', 'customerSearch')" style="padding: 10px 15px; background-color: #1a73e8; color: #ffffff; border: none; border-radius: 5px; cursor: pointer;">Search</button>
        </div>
        <table id="customerTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>
                        <a href="{{ route('admin.edit', $customer->user_id) }}">Edit</a>
                        <form action="{{ route('admin.destroy', $customer->user_id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <input type="submit" value="Delete">
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Businesses Tab Content -->
    <div id="businesses" class="tab-content" style="display: none;">
        <h2>Business Listings</h2>
        <div class="search-bar" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <input type="text" id="businessSearch" placeholder="Search businesses..." style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 70%;">
            <button onclick="filterTable('businessTable', 'businessSearch')" style="padding: 10px 15px; background-color: #1a73e8; color: #ffffff; border: none; border-radius: 5px; cursor: pointer;">Search</button>
        </div>
        <table id="businessTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($businesses as $business)
                <tr>
                    <td>{{ $business->name }}</td>
                    <td>{{ $business->email }}</td>
                    <td>
                        <a href="{{ route('admin.edit', $business->user_id) }}">Edit</a>
                        <form action="{{ route('admin.destroy', $business->user_id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <input type="submit" value="Delete">
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    // Tab functionality
    function openTab(evt, tabName) {
        let i, tabContent, tabButtons;
        tabContent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabContent.length; i++) {
            tabContent[i].style.display = "none";
        }
        tabButtons = document.getElementsByClassName("tab-button");
        for (i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove("active");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.classList.add("active");
    }

    // Filter Table Functionality
    function filterTable(tableId, searchId) {
        const input = document.getElementById(searchId);
        const filter = input.value.toLowerCase();
        const table = document.getElementById(tableId);
        const rows = table.getElementsByTagName("tr");

        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName("td");
            let match = false;
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].innerText.toLowerCase().includes(filter)) {
                    match = true;
                    break;
                }
            }
            rows[i].style.display = match ? "" : "none";
        }
    }

    // Activate the first tab by default
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelector(".tab-button.active").click();
    });
</script>

@endsection
