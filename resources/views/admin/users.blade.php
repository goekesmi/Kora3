@extends('app', ['page_title' => 'Users', 'page_class' => 'admin-users'])

@section('header')
  <section class="head">
    <div class="inner-wrap center">
      <h1 class="title">
        <i class="icon icon-users"></i>
        <span>User Management</span>
      </h1>
      <p class="description">Brief info on user management,
        followed by instructions on how to use the user management page will go here.
        If you would to manage users on a project or form level,
        go to the according permissions page within the project or form.</p>
    </div>
  </section>
@stop

@section('body')
  <section class="filters center">
    <div class="underline-middle search search-js">
      <i class="icon icon-search"></i>
      <input type="text" placeholder="Find a User">
      <i class="icon icon-cancel icon-cancel-js"></i>
    </div>
    <div class="sort-options sort-options-js">
      <!--<a href="#" class="text dropdown-white-toggle dropdown-white-toggle-js underline-middle-hover">
          <span>Alphabetical (A-Z)</span>
          <i class="icon icon-chevron"></i>
      </a>-->
      <select class="order option-dropdown-js underline-middle" id="order-dropdown">
          <option value="az">Alphabetical (A-Z)</option>
          <option value="za">Alphabetical (Z-A)</option>
          <option value="nto">Newest to Oldest</option>
          <option value="otn">Oldest to Newest</option>
      </select>
    </div>
  </section>

  <section class="new-object-button new-object-button-js center">
    <input type="button" value="Invite New User(s)">
  </section>

  <section class="user-selection user-selection-js center">
    @include('partials.admin.userManagement.users-sorted')

    <div class="modal modal-js modal-mask users-cleanup-modal-js">
      <div class="content">
        <div class="header">
          <span class="title title-js"></span>
          <a href="#" class="modal-toggle modal-toggle-js">
            <i class="icon icon-cancel"></i>
          </a>
        </div>
        <div class="body">
          @include("partials.admin.userManagement.userDeleteForm")
          @include("partials.admin.userManagement.inviteForm")
        </div>
      </div>
    </div>
  </section>
@stop


@section('javascripts')
  @include('partials.admin.javascripts')

  <script type="text/javascript">
    var CSRFToken = '{{ csrf_token() }}';
    Kora.Admin.Users();

    /**
     * Check the boxes for a particular user.
     */
    function checker(card, action) {
        // If they are an admin, check the admin box.
        if (action == "admin") {
            var admin = card.find('#admin');
            admin.prop('checked', !admin.prop('checked'));
        }

        // If they are an active user, check the active box.
        if (action == "activation") {
            var check = card.find('#active')
            check.prop('checked', !check.prop('checked'));
        }
    }
  </script>
@stop
