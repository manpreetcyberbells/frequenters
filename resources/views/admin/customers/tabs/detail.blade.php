<div class="card-body">
    <div class="row">
    <div class="col-7 col-sm-12" bis_skin_checked="1">
        <div class="tab-content" id="vert-tabs-tabContent" bis_skin_checked="1">
            <div class="tab-pane text-left fade show active" id="vert-tabs-home" role="tabpanel" aria-labelledby="vert-tabs-home-tab" bis_skin_checked="1">
                <div class="card" bis_skin_checked="1">
                <div class="card-header" bis_skin_checked="1">
                    <div class="user-block" bis_skin_checked="1">
                        <img class="img-circle img-bordered-sm" src="{{ $customer->profile_image }}" alt="user image">
                        <span class="username">
                        <a href="#">{{ $customer->name }}</a>
                        </span>
                    </div>
                </div>
                <div class="card-body" bis_skin_checked="1">
                    <div class="row" bis_skin_checked="1">
                        <div class="col-12 col-md-12 col-lg-6 order-1 order-md-2" bis_skin_checked="1">
                            <div class="card card-primary" bis_skin_checked="1">
                            <div class="card-body" bis_skin_checked="1">
                                <strong><i class="fa fa-phone"></i> Customer Phone</strong>
                                <p class="text-muted">
                                    {{ $customer->phone }}
                                </p>
                                <hr>
                                <strong><i class="fa fa-envelope" aria-hidden="true"></i> Customer Email</strong>
                                <p class="text-muted">{{ $customer->email }}</p>
                                <hr>
                                <strong><i class="fas fa-map-marker-alt mr-1"></i> Location</strong>
                                <p class="text-muted">{{ $customer['customerAddress']->address_line1}}</p>
                                <hr>
                                <strong><i class="far fa-file-alt mr-1"></i>Status</strong>
                                <span class="badge @if($customer->status == 'active')bg-primary @else bg-danger @endif" style="text-transform: capitalize;">{{ $customer->status }}</span>
                                <hr>
                                <strong><i class="fa fa-envelope" aria-hidden="true"></i> Providers</strong>
                                <p class="text-muted" style="text-transform: capitalize;">{{ $customer->providers }}</p>
                                <hr>
                                <strong><i class="fa fa-sign-in" aria-hidden="true"></i> Last Login</strong>
                                <p class="text-muted">
                                    @if ($customer->last_login_at)
                                        {{ \Carbon\Carbon::parse($customer->last_login_at)->format('d M Y, h:i A') }}
                                    @else
                                        <span class="badge bg-danger">Never Logged In</span>
                                    @endif
                                </p>
                            </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-12 col-lg-6 order-1 order-md-2" bis_skin_checked="1">
                            <div class="card card-primary" bis_skin_checked="1">
                            <div class="card-body" bis_skin_checked="1">
                                <strong><i class="fa fa-pointer"></i> Points Balance</strong>
                                <p class="text-muted">
                                    {{ @$customer['customerProfile']->points_balance }}
                                </p>
                                <hr>
                                <strong><i class="fa fa-birthday-cake" aria-hidden="true"></i> Birthday</strong>
                                <p class="text-muted">{{ @$customer['customerProfile']->birthday }}</p>
                                <hr>
                                <strong><i class="fa fa-calendar"></i> Anniversary</strong>
                                <p class="text-muted">{{ $customer['customerProfile']->anniversary}}</p>
                                <hr>
                                <strong><i class="far fa-file-alt mr-1"></i>Anonymous Name</strong>
                                <p class="text-muted">{{ $customer['customerProfile']->anonymous_name }}</p>
                                <hr>
                                <strong><i class="fa fa-picture-o" aria-hidden="true"></i>Anonymous Image</strong>
                                <div class="user-block1" bis_skin_checked="1">
                                    <img class="img-circle img-bordered-sm" src="{{ $customer['customerProfile']->anonymous_image }}" alt="user image">
                                </div>
                                <hr>
                            </div>
                            </div>
                        </div>

                    </div>
                </div>
                </div>
            </div>
            <div class="tab-pane fade" id="vert-tabs-profile" role="tabpanel" aria-labelledby="vert-tabs-profile-tab" bis_skin_checked="1">
                Contest                                        
            </div>
            <div class="tab-pane fade" id="vert-tabs-messages" role="tabpanel" aria-labelledby="vert-tabs-messages-tab" bis_skin_checked="1">
                Events                                        
            </div>
            <div class="tab-pane fade" id="vert-tabs-settings" role="tabpanel" aria-labelledby="vert-tabs-settings-tab" bis_skin_checked="1">
                offer                                        
            </div>
            <div class="tab-pane fade" id="vert-tabs-reward" role="tabpanel" aria-labelledby="vert-tabs-reward-tab" bis_skin_checked="1">
                reward                                        
            </div>
            <div class="tab-pane fade" id="vert-tabs-staff" role="tabpanel" aria-labelledby="vert-tabs-staff-tab" bis_skin_checked="1">
                staff                                        
            </div>
            <div class="tab-pane fade" id="vert-tabs-menu" role="tabpanel" aria-labelledby="vert-tabs-menu-tab" bis_skin_checked="1">
                menu                                        
            </div>
            <div class="tab-pane fade" id="vert-tabs-services" role="tabpanel" aria-labelledby="vert-tabs-services-tab" bis_skin_checked="1">
                service                                        
            </div>
        </div>
    </div>
    </div>
</div>