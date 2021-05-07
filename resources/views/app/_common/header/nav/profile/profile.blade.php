<li class="profile dropdown">
    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true"
       aria-expanded="false">
        <span class="name">{{ Auth::user()->name }}</span>
    </a>
    <div class="dropdown-menu profile-dropdown-menu" aria-labelledby="dropdownMenu1">
        {{ Form::open(['id' => 'logout-form', 'method' => 'POST', 'route' => 'logout']) }}
        {{ Form::button('<i class="fa fa-power-off icon"></i> '.__('Logout'), ['class' => 'dropdown-item', 'type' => 'submit']) }}
        {{ Form::close() }}
    </div>
</li>