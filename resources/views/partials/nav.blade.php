<nav class="navbar navbar-inverse navbar-fixed-top" style="background-image: inherit">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="{{ url('/') }}"><img style="height: 35px;width: 35px;margin-top: -7.5px" src="{{ env('BASE_URL') }}public/logos/KoraIII-Logo.gif"></a>
        </div>
		
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <!-- Left justified links -->
              @if (\Auth::user() != null && \Auth::user()->admin)
                  @include('partials.menu.dashboard')
              @else
                  <li><a href="{{ url('/projects') }}">{{trans('partials_nav.dashboard')}}</a></li>
              @endif
              @yield('leftNavLinks')
          </ul>
		  <ul class="nav navbar-nav navbar-right">
		    <!-- Right justified links -->
			@if (Auth::guest())
				<li><a href="{{ url('/auth/login') }}">{{trans('partials_nav.login')}}</a></li>
				<li><a href="{{ url('/auth/register') }}">{{trans('partials_nav.register')}}</a></li>
                <li><a href="{{ action('Auth\UserController@activateshow') }}">{{trans('partials_nav.activation')}}</a></li>
			@else
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ Auth::user()->username }} <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="{{ url('/user') }}">{{trans('partials_nav.viewprofile')}}</a></li>
						<li><a href="{{ url('/auth/logout') }}">{{trans('partials_nav.logout')}}</a></li>
					</ul>
				</li>
              @if (!\Auth::user()->active)
                  <li><a href="{{ action('Auth\UserController@activateshow') }}">{{trans('partials_nav.activation')}}</a></li>
              @endif
			@endif
		</ul>
        </div><!--/.nav-collapse -->
      </div>
</nav>