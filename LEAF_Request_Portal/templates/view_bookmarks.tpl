<div id="toolbar" class="toolbar_right toolbar noprint">
    <div id="tools" class="tools">Tools:<br />
    </div>
<!--
    <div id="category_list">Quick Links:<br />
    <!--{if $is_service_chief == true}-->
        <div><a href="?a=service_chief" style="text-decoration: none"><img src="../libs/dynicons/?img=accessories-text-editor.svg&amp;w=32" style="float: left; padding: 2px" alt="Service Prioritization" title="Service Prioritization" /> Service Prioritization for Equipment/FTE</a></div>
    <!--{/if}-->
    <!--{if $ingroup_quadrad == true}-->
    <div><a href="?a=quadrad_equipment_review" style="text-decoration: none"><img src="../libs/dynicons/?img=utilities-system-monitor.svg&amp;w=32" style="float: left; padding: 2px" alt="Quadrad Prioritization" title="Quadrad Prioritization" /> Quadrad Equipment Prioritization</a></div>
    <div><a href="?a=quadrad_fte_review" style="text-decoration: none"><img src="../libs/dynicons/?img=system-users.svg&amp;w=32" style="float: left; padding: 2px" alt="Quadrad Prioritization" title="Quadrad Prioritization" /> Quadrad FTE Prioritization</a></div>
    <!--{/if}-->
    </div>
-->
</div>

<!--{foreach from=$bookmarks item=record}-->
<div id="bookmark_<!--{$record.recordID}-->" style="float: left; border: 1px solid black; margin: 8px; padding: 0px; background-color: <!--{$record.stepBgColor}-->; color: <!--{$record.stepFontColor}-->; width: 370px">    
    <div style="float: left; cursor: pointer; background-color: black; font-size: 150%; font-weight: bold; color: white; padding: 8px; text-align: center" onclick="window.location='?a=printview&amp;recordID=<!--{$record.recordID}-->'">
        <!--{$record.recordID}--><br />
        <!--{if $record.actionIcon != ''}-->
        <img src="../libs/dynicons/?img=<!--{$record.actionIcon}-->&amp;w=32" alt="<!--{$record.stepTitle}--> <!--{$record.actionTextPasttense}-->" title="<!--{$record.stepTitle}--> <!--{$record.actionTextPasttense}-->" />
        <!--{else}-->
        <img src="../libs/dynicons/?img=emblem-notice.svg&amp;w=32" alt="<!--{$record.stepTitle}--> <!--{$record.actionTextPasttense}-->" title="<!--{$record.stepTitle}--> <!--{$record.actionTextPasttense}-->" />
        <!--{/if}-->
    </div>
    <div>
        <div style="background-color: #e0e0e0; font-weight: bold; border-bottom: 1px solid black; padding: 2px">
                        <span style="float: right; cursor: pointer"><img src="../libs/dynicons/?img=process-stop.svg&amp;w=16" alt="Delete Bookmark" title="Delete Bookmark" onclick="removeBookmark(<!--{$record.recordID}-->)"/></span>
                <span style="padding: 4px; font-size: 140%"><a href="?a=printview&amp;recordID=<!--{$record.recordID}-->" style="text-decoration: none"><!--{$record.title|truncate:25:"...":true}--></a></span>
        </div>    
        <div style="padding: 4px">
            <!--{if $record.submitted == 0}-->                
                <!--{if $record.stepTitle != ''}-->
                    <span style="padding: 4px; font-weight: bold">Status:</span>
                    <!--{$record.lastStatus}--><br /><br />
                <!--{/if}-->
                <span style="padding: 4px; font-weight: bold">This form is available for editing.
                </span>
            <!--{else if $record.stepTitle != ''}-->
                <span style="padding: 4px; font-weight: bold">Status:</span>
                <!--{$record.lastStatus}-->
            <!--{else}-->
                <span style="padding: 4px">Request submitted, pending initial review</span>
                <!--{$record.lastStatus}-->
            <!--{/if}-->
        </div>
    </div>
</div>
<!--{/foreach}-->

<!--{if count($bookmarks) == 0}-->
<br style="clear: both" />
<div style="width: 50%; margin: 0px auto; border: 1px solid black; padding: 16px; background-color: #fffcc9">
<img src="../libs/dynicons/?img=help-browser.svg&amp;w=96" alt="empty" style="float: left"/><span style="font-size: 200%"> You do not have any requests bookmarked!<br /><br />To bookmark a request, open a request and select "Add Bookmark".</span>
</div>
<!--{/if}-->

<script type="text/javascript">
/* <![CDATA[ */

function removeBookmark(recordID) {
    dojo.style('bookmark_' + recordID, 'opacity', '0.2');
    dojo.xhrPost({
        url: "ajaxIndex.php?a=removebookmark&recordID=" + recordID,
        content: {CSRFToken: '<!--{$CSRFToken}-->'},
        load: function(response, ioArgs) {
        },
        preventCache: true
    });
}

//attempt to force a consistent width for the sidebar if there is enough desktop resolution
var lastScreenSize = null;
function sideBar() {
//    console.log(dojo.body().clientWidth);
    if(lastScreenSize != dojo.body().clientWidth) {
        lastScreenSize = dojo.body().clientWidth;

        if(lastScreenSize < 700) {
            mainWidth = lastScreenSize * 0.97;
            dojo.removeClass("toolbar", "toolbar_right");
            dojo.addClass("toolbar", "toolbar_inline");
            dojo.style("toolbar", "width", "98%");
        }
        else {
            mainWidth = (lastScreenSize * 0.8) - 2;
            dojo.removeClass("toolbar", "toolbar_inline");
            dojo.addClass("toolbar", "toolbar_right");
            // effective width of toolbar becomes around 200px
            mywidth = Math.floor((1 - 200/lastScreenSize) * 100);
            dojo.style("toolbar", "width", 98-mywidth + "%");
        }
    }
}

dojo.addOnLoad(function() {
    sideBar();
    setInterval("sideBar()", 500);
});

/* ]]> */
</script>