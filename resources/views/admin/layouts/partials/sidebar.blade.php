<!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
      <img src="{{ asset('admin/images/Frequenters_logo_1.png') }}" alt="Frequenters Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Frequenters</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="{{url('')}}/{{ Auth::user()->profile_image }}" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">{{ Auth::user()->name }}</a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item menu-open">
            <!-- <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard<i class="right fas fa-angle-left"></i></p>
            </a> -->
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                  <p>Dashboard</p>
                </a>
              </li>
            </ul>
          </li>

            <li class="nav-item {{ request()->routeIs('customers.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="fa fa-users" aria-hidden="true"></i>
                  <p>
                    Customers
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.index') ? 'active' : '' }}">
                      <i class="fa fa-users" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('customers.create') }}" class="nav-link {{ request()->routeIs('customers.create') ? 'active' : '' }}">
                      <i class="fa fa-user-plus" aria-hidden="true"></i>
                      <p>Create Customer</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('businesses.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('businesses.*') ? 'active' : '' }}">
                <i class="fa fa-briefcase" aria-hidden="true"></i>
                  <p>
                    Businesses
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('businesses.index') }}" class="nav-link {{ request()->routeIs('businesses.index') ? 'active' : '' }}">
                    <i class="fa fa-building" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('businesses.create') }}" class="nav-link {{ request()->routeIs('businesses.create') ? 'active' : '' }}">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Business</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('contests.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('contests.*') ? 'active' : '' }}">
                <i class="fa fa-calendar" aria-hidden="true"></i>
                  <p>
                    Contests
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('contests.index') }}" class="nav-link {{ request()->routeIs('contests.index') ? 'active' : '' }}">
                    <i class="fa fa-building" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('contests.create') }}" class="nav-link {{ request()->routeIs('contests.create') ? 'active' : '' }}">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Contests</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('events.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">
                <i class="fa fa-calendar" aria-hidden="true"></i>
                  <p>
                    Events
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('events.index') }}" class="nav-link {{ request()->routeIs('events.index') ? 'active' : '' }}">
                    <i class="fa fa-building" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('events.create') }}" class="nav-link {{ request()->routeIs('events.create') ? 'active' : '' }}">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Events</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('admin.business.offer.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('admin.business.offer.*') ? 'active' : '' }}">
                <i class="fa fa-percent" aria-hidden="true"></i>
                  <p>
                    Offers
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('admin.business.offer.index') }}" class="nav-link {{ request()->routeIs('admin.business.offer.index') ? 'active' : '' }}">
                    <i class="fa fa-building" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('admin.business.offer.create') }}" class="nav-link {{ request()->routeIs('admin.business.offer.create') ? 'active' : '' }}">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Offer</p>
                    </a>
                  </li>
                </ul>
              </li>


              <li class="nav-item {{ request()->routeIs('rewards.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('rewards.*') ? 'active' : '' }}">
                <i class="fa fa-gift" aria-hidden="true"></i>
                  <p>
                    Rewards
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('rewards.index') }}" class="nav-link {{ request()->routeIs('rewards.index') ? 'active' : '' }}">
                    <i class="fa fa-building" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('rewards.create') }}" class="nav-link {{ request()->routeIs('rewards.create') ? 'active' : '' }}">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Reward</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('staff.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                <i class="fa fa-users" aria-hidden="true"></i>
                  <p>
                  Staff
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('staff.index') }}" class="nav-link {{ request()->routeIs('staff.index') ? 'active' : '' }}">
                    <i class="fa fa-users" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('staff.create') }}" class="nav-link {{ request()->routeIs('staff.create') ? 'active' : '' }}">
                    <i class="fa fa-user-square" aria-hidden="true"></i>
                      <p>Create Employee</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('menus.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('menus.*') ? 'active' : '' }}">
                <i class="fa fa-bars" aria-hidden="true"></i>
                  <p>
                  Menu
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('menus.index') }}" class="nav-link {{ request()->routeIs('menus.index') ? 'active' : '' }}">
                    <i class="fa fa-list-ol" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('menus.create') }}" class="nav-link {{ request()->routeIs('menus.create') ? 'active' : '' }}">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Menu</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('services.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
                <i class="fa fa-bars" aria-hidden="true"></i>
                  <p>
                  Service
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}">
                    <i class="fa fa-list-ol" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('services.create') }}" class="nav-link {{ request()->routeIs('services.create') ? 'active' : '' }}">
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Service</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item {{ request()->routeIs('categories.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                  <i class="fas fa-table" ></i>
                  <p>Categories<i class="nav-arrow bi bi-chevron-right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.index') ? 'active' : '' }}">
                      <i class="fa fa-list-alt" aria-hidden="true"></i>
                      <p>Listing</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('categories.create') }}" class="nav-link {{ request()->routeIs('categories.create') ? 'active' : '' }}">
                      <i class="fa fa-plus-square" aria-hidden="true"></i>
                      <p>Create Category</p>
                    </a>
                  </li>
                </ul>
              </li>

              <!-- <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="fa fa-list-alt" aria-hidden="true"></i>
                  <p>Manual CheckIn</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                  <p>Business Points</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                  <p>Rewards</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                  <p>Offers</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                  <p>Points</p>
                </a>
              </li> -->
            <li class="nav-item">
              <a href="pages/kanban.html" class="nav-link">
                <p>
                  <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="width: 100%;">Sign Out</button>
                  </form>
                </p>
              </a>
            </li>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>