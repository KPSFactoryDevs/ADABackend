
<div class="table-responsive">
    <table stye="background-color: white;" class="table table-borderless">
        <tr>
            <th>@lang('Permessi')</th>
            <td>@include('backend.auth.user.includes.type', ['user' => $logged_in_user])</td>
        </tr>

        <tr>
            <th>@lang('Avatar')</th>
            <!-- <td><img src="{{ $logged_in_user->avatar }}" class="user-profile-image" /></td> -->
            <td>
            <form  method="POST" action="{{ url('account') }}" accept-charset="UTF-8" enctype="multipart/form-data">
            @csrf  

            @method('POST')
            <button class="btn btn-outline-success btn-lg float-right btn-sm" type="submit">Carica</button>
            <div class="profile-pic-wrapper">
                <div class="pic-holder">    
                    <!-- uploaded pic shown here -->
                    @if(Auth::user()->profile_pic == null)
                    <img src="{{ $logged_in_user->avatar }}" class="user-profile-image" />
                    @else
                    <img id="profilePic" class="pic" src="/uploads/{{ $fileName }}">
                    @endif
                    <label for="newProfilePhoto" class="upload-file-block">
                    <div class="text-center">
                        <div class="mb-2">
                        <i class="fa fa-camera fa-2x"></i>
                        </div>
                        <div class="text-uppercase">
                        Aggiorna
                        </div>
                    </div>
                    </label>
                    <input class="uploadProfileInput" type="file" name="profile_pic" id="newProfilePhoto" accept="jpeg,png,jpg,gif,svg" style="display: none;" />
                </div>
            </div>
            </form>
            </td>
        </tr>

        <tr>
            <th>@lang('Nome')</th>
            <td>{{ $logged_in_user->name }}</td>
        </tr>

        <tr>
            <th>@lang('E-mail')</th>
            <td>{{ $logged_in_user->email }}</td>
        </tr>

        @if ($logged_in_user->isSocial())
            <tr>
                <th>@lang('Social Provider')</th>
                <td>{{ ucfirst($logged_in_user->provider) }}</td>
            </tr>
        @endif

        <tr>
            <th>@lang('Fuso orario')</th>
            <td>{{ $logged_in_user->timezone ? str_replace('_', ' ', $logged_in_user->timezone) : __('N/A') }}</td>
        </tr>

        <!-- <tr>
            <th>@lang('Creazione account')</th>
            <td>@displayDate($logged_in_user->created_at) ({{ $logged_in_user->created_at->diffForHumans() }})</td>
        </tr> -->

        <tr>
            <th>@lang('Ultimo aggiornamento')</th>
            <td>@displayDate($logged_in_user->updated_at) ({{ $logged_in_user->updated_at->diffForHumans() }})</td>
        </tr>
    </table>
</div><!--table-responsive-->
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script>
    $(document).on("change", ".uploadProfileInput", function () {
  var triggerInput = this;
  var currentImg = $(this).closest(".pic-holder").find(".pic").attr("src");
  var holder = $(this).closest(".pic-holder");
  var wrapper = $(this).closest(".profile-pic-wrapper");
  $(wrapper).find('[role="alert"]').remove();
  var files = !!this.files ? this.files : [];

  if (!files.length || !window.FileReader) {
    return;
  }
  if (/^image/.test(files[0].type)) {
    // only image file
    var reader = new FileReader(); // instance of the FileReader
    reader.readAsDataURL(files[0]); // read the local file
   

    reader.onloadend = function () {
      $(holder).addClass("uploadInProgress");
      $(holder).find(".pic").attr("src", this.result);
      $(holder).append(
        '<div class="upload-loader"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>'
      );
      // Dummy timeout; call API or AJAX below
      setTimeout(() => {
        $(holder).removeClass("uploadInProgress");
        $(holder).find(".upload-loader").remove();

        // If upload successful
        if (Math.random() < 0.9) {
          $(wrapper).append(
            '<div class="snackbar show" role="alert"><i class="fa fa-check-circle text-success"></i> Profile image updated successfully</div>'
          );

          // Clear input after upload
          // $(triggerInput).val("");

          setTimeout(() => {
            $(wrapper).find('[role="alert"]').remove();
          }, 3000);
        } else {
          $(holder).find(".pic").attr("src", currentImg);
          $(wrapper).append(
            '<div class="snackbar show" role="alert"><i class="fa fa-times-circle text-danger"></i> There is an error while uploading! Please try again later.</div>'
          );

          // Clear input after upload
          // $(triggerInput).val("");
          setTimeout(() => {
            $(wrapper).find('[role="alert"]').remove();
          }, 3000);
        }
      }, 1500);
    };
  } else {
    $(wrapper).append(
      '<div class="alert alert-danger d-inline-block p-2 small" role="alert">Please choose the valid image.</div>'
    );
    setTimeout(() => {
      $(wrapper).find('role="alert"').remove();
    }, 3000);
  }
});

</script>