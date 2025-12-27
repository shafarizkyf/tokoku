<!-- Dynamic description -->
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $url }}">
<!-- Open Graph -->
<meta property="og:title" content="{{ $productName }}">
<meta property="og:description" content="Beli {{ $productName }} di {{ config('app.name') }}">
<meta property="og:type" content="product">
<meta property="og:url" content="{{ $url }}">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $productName }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $imageUrl }}">