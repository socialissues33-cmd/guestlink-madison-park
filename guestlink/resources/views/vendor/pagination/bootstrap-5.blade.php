@if ($paginator->hasPages())
    <nav class="d-flex align-items-center justify-content-between">
        <div class="d-flex justify-content-between flex-fill d-sm-none w-100">
            <ul class="pagination mb-0 w-100 justify-content-between">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled"><span class="page-link" style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#8899AA; border-radius:8px;">&lsaquo; Previous</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#C9A84C; border-radius:8px;">&lsaquo; Previous</a></li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#C9A84C; border-radius:8px;">Next &rsaquo;</a></li>
                @else
                    <li class="page-item disabled"><span class="page-link" style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#8899AA; border-radius:8px;">Next &rsaquo;</span></li>
                @endif
            </ul>
        </div>
        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <div>
                <p style="font-size:0.78rem; color:#8899AA; margin:0;">
                    Showing
                    <span style="color:#C9A84C; font-weight:600;">{{ $paginator->firstItem() }}</span>
                    to
                    <span style="color:#C9A84C; font-weight:600;">{{ $paginator->lastItem() }}</span>
                    of
                    <span style="color:#C9A84C; font-weight:600;">{{ $paginator->total() }}</span>
                    results
                </p>
            </div>

            <div>
                <ul class="pagination mb-0" style="gap:4px;">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link" style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#8899AA; border-radius:8px;">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                               style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#C9A84C; border-radius:8px; transition:all 0.2s;">&lsaquo;</a>
                        </li>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="page-item disabled"><span class="page-link" style="background:transparent; border:none; color:#8899AA;">{{ $element }}</span></li>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active">
                                        <span class="page-link" style="background:linear-gradient(135deg,#C9A84C,#E0C06E); border:none; color:#0F1B2D; font-weight:700; border-radius:8px;">{{ $page }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $url }}"
                                           style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#8899AA; border-radius:8px; transition:all 0.2s;">{{ $page }}</a>
                                    </li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
                               style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#C9A84C; border-radius:8px; transition:all 0.2s;">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link" style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,168,76,0.2); color:#8899AA; border-radius:8px;">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
