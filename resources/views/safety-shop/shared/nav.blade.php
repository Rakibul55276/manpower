<link rel="stylesheet" href="{{ asset('css/safety-shop/sales/sales.css') }}">
<script defer src="{{ asset('js/safety-shop/image-preview.js') }}?v={{ is_file(public_path('js/safety-shop/image-preview.js')) ? filemtime(public_path('js/safety-shop/image-preview.js')) : '1' }}"></script>
@if(auth()->user()->canOperateSafetyShop())
<a class="global-pos-shortcut {{ request()->routeIs('safety-shop.sales.create')?'active':'' }}" href="{{ route('safety-shop.sales.create') }}" aria-label="Open POS checkout"><span aria-hidden="true">▦</span> POS</a>
@endif
