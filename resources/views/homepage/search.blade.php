@extends('layouts.app', ['type' => 'homepage'])

@section('css')
    <style>
        .filter-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .filter-group {
            margin-bottom: 20px;
        }
        .filter-label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #555;
        }
        .price-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .price-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .price-separator {
            color: #888;
        }
        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            cursor: pointer;
        }
        .filter-checkbox input {
            width: 16px;
            height: 16px;
        }
        .filter-checkbox span {
            font-size: 14px;
            color: #555;
        }
        .sort-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
            cursor: pointer;
        }
        .apply-filter-btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .apply-filter-btn:hover {
            background: #0056b3;
        }
        .reset-filter-btn {
            width: 100%;
            padding: 10px;
            background: #f8f9fa;
            color: #555;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .reset-filter-btn:hover {
            background: #e9ecef;
        }
    </style>
@endsection

@section('js')
    <script src="https://unpkg.com/infinite-scroll@5/dist/infinite-scroll.pkgd.min.js"></script>
    <script src="{{ asset('js/homepage/search.js') }}"></script>
@endsection

@section('content')
    <div class="container">
        <div class="row" style="margin-top: 80px;">
            <div class="col-md-3">
                <div class="filter-section">
                    <div class="filter-title">Filter</div>
                    
                    <div class="filter-group">
                        <div class="filter-label">Urutkan</div>
                        <select class="sort-select" id="sortBy">
                            <option value="latest">Terbaru</option>
                            <option value="price_asc">Harga: Rendah ke Tinggi</option>
                            <option value="price_desc">Harga: Tinggi ke Rendah</option>
                            <option value="popular">Terpopuler</option>
                            <option value="rating">Rating Tertinggi</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <div class="filter-label">Kondisi</div>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="condition" value="new" checked>
                            <span>Baru</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="condition" value="used" checked>
                            <span>Bekas</span>
                        </label>
                    </div>

                    <div class="filter-group">
                        <div class="filter-label">Rentang Harga (Rp)</div>
                        <div class="price-inputs">
                            <input type="number" class="price-input" id="minPrice" placeholder="Min" min="0">
                            <span class="price-separator">-</span>
                            <input type="number" class="price-input" id="maxPrice" placeholder="Max" min="0">
                        </div>
                    </div>

                    <button class="apply-filter-btn" id="applyFilters">Terapkan Filter</button>
                    <button class="reset-filter-btn" id="resetFilters">Reset Filter</button>
                </div>
            </div>
            
            <div class="col-md-9 py-3">
                <div class="product-list d-flex gap-3 flex-wrap"></div>
            </div>
        </div>
    </div>
@endsection
