<% if $Notices %>
    <% loop $Notices %>
        <div class="message $MessageType">$Message</div>
    <% end_loop %>
<% end_if %>
