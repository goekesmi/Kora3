<input type="hidden" id="regtoken" name="regtoken" value="{{\App\Http\Controllers\Auth\RegisterController::makeRegToken()}}">

<input type="hidden" id="uid" name="uid" value="{{$user->id}}">

<div class="form-group mt-xl">
  <label for="username">User Name</label>
  <input disabled type="text" class="text-input" id="username" name="username" placeholder="Enter username here" value="{{ $user->username }}">
</div>

<div class="form-group mt-xl">
  <label for="email">Email Address</label>
  <input disabled type="email" class="text-input" id="email" name="email" placeholder="Enter email here" value="{{ $user->email }}">
</div>

<div class="form-group half mt-xl">
  <label for="first-name">First Name</label>
  <input type="text" class="text-input" id="first_name" name="first_name" placeholder="Enter first name here" value="{{ $user->first_name }}">
</div>

<div class="form-group half mt-xl">
  <label for="first-name">Last Name</label>
  <input type="text" class="text-input" id="last_name" name="last_name" placeholder="Enter last name here" value="{{ $user->last_name }}">
</div>

<div class="form-group mt-xl">
  <label>Profile Image</label>
  <input type="file" accept="image/*" name="profile" id="profile" class="profile-input" />
  <label for="profile" class="profile-label">
      @if ($user->profile)
        <div class="icon-user-cont"><img src="{{ $user->getProfilePicUrl() }}" alt='Profile Picture'></div>
        <p class="filename">{{ $user->getProfilePicFilename() }}</p>
      @else
        <div class="icon-user-cont"><i class="icon icon-user"></i></div>
        <p class="filename">Add a photo to help others identify you</p>
      @endif
    <p class="instruction mb-0 @if($user->profile) photo-selected @endif">
      <span class="dd">Drag and Drop or Select a Photo here</span>
      <span class="no-dd">Select a Photo here</span>
      <span class="select-new">Select a Different Photo?</span>
    </p>
  </label>
</div>

<div class="form-group mt-xl">
  <label for="organization">Organization</label>
  <input type="text" class="text-input" id="organization" name="organization" placeholder="Enter organization here" value="{{ $user->organization }}">
</div>

<div class="form-group mt-xl">
    <label for="language">Language</label>
    <select id="language" name="language" class="chosen-select">
        {{$languages_available = Config::get('app.locales_supported')}}
        @foreach($languages_available->keys() as $lang)
            <option value='{{$languages_available->get($lang)[0]}}'>{{$languages_available->get($lang)[1]}} </option>
        @endforeach
    </select>
</div>

<h2 class="mt-xxxl mb-xl">Update Password</h2>

<div class="form-group mt-xl">
  <label for="new_password">Enter New Password</label>
  <input type="password" class="text-input" id="new_password" name="new_password" placeholder="Enter password here">
</div>

<div class="form-group mt-xl">
  <label for="confirm">Confirm New Password</label>
  <input type="password" class="text-input" id="confirm" name="confirm" placeholder="Enter password here" disabled>
</div>

<div class="form-group mt-100-xl" >
    {!! Form::submit('Update Profile', ['class' => 'btn edit-btn update-user-submit pre-fixed-js']) !!}
</div>

<div class="form-group mt-100-xl">
    @if ($type == 'edit' && \Auth::user()->id != 1)
        <div class="delete-user">
            <a class="btn dot-btn trash warning user-trash-js" data-title="Delete User?" href="#">
                <i class="icon icon-trash"></i>
            </a>
        </div>
    @else
        <div class="spacer invisible"></div>
    @endif
</div>
