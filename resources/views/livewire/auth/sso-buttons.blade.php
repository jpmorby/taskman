<div class="form-group">
    <div class="col-md-6 col-md-offset-4 text-center">
        @if (Route::has('login.socialite'))
            <a href="{{ url('/login/github') }}" class="btn btn-github"><i class="fa fa-github"></i> Github</a>
            |

            <a href="{{ url('/login/google') }}" class="btn btn-google"><i class="fa fa-google"></i> Google</a>

            {{-- |
            <a href="{{ url('/login/discord') }}"><i class="fa-brands fa-discord"></i> Discord</a> --}}
        @endif
    </div>
</div>