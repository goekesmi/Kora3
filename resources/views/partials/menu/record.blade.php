<li class="navigation-item">
    <a href="#" class="menu-toggle navigation-toggle-js">
        <i class="icon icon-minus mr-sm"></i>
        <span>{{ $pid . '-' . $fid . '-' . $rid }}</span>
        <i class="icon icon-chevron"></i>
    </a>

    <ul class="navigation-sub-menu navigation-sub-menu-js">
        <li class="link link-head">
            <a href="{{action("RecordController@show", ['pid'=>$pid,'fid'=>$fid,'rid'=>$rid])}}">
                <i class="icon icon-record"></i>
                <span>{{ $pid . '-' . $fid . '-' . $rid }}</span>
            </a>
        </li>

        <li class="spacer full"></li>

        <li class="link first">
            <a href="{{action("RecordController@edit", ['pid'=>$pid, 'fid'=>$fid, 'rid'=>$rid])}}">Edit Record</a>
        </li>

        <li class="link">
            <a href="{{action('RecordController@cloneRecord', ['pid'=>$pid, 'fid'=>$fid, 'rid'=>$rid])}}">Duplicate Record</a>
        </li>

        <li class="link">
            <a href="{{action("RevisionController@show", ['pid'=>$pid, 'fid'=>$fid, 'rid'=>$rid])}}">View Revisions ({{\App\Http\Controllers\RevisionController::getRevisionCount($rid)}})</a>
        </li>

        <li class="link">
            <a href="#">Designate as Preset</a>
        </li>
    </ul>
</li>