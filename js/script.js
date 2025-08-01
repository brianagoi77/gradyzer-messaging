jQuery(document).ready(function($) {
  let activeUserId = null;
  let activeMessageId = null;

  function loadThread(userId, messageId) {
    const threadBox = $('.message-content');
    const replyForm = $('.reply-form');

    replyForm.show().data('user', userId).data('message', messageId);
    activeUserId = userId;
    activeMessageId = messageId;

    $.ajax({
      url: GradyzerSettings.restRoot + 'thread/' + userId,
      method: 'GET',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', GradyzerSettings.nonce);
      },
      success: function(response) {
        threadBox.empty();

        if (response.product) {
          threadBox.append(
            `<div class="product-meta">
              <img src="${response.product.thumb}" class="product-thumb" />
              <div class="product-info">
                <strong>${response.product.title}</strong>
                <span>${response.product.price}</span>
              </div>
            </div>`
          );
        }

        response.messages.forEach(msg => {
          threadBox.append(
            `<div class="message-block">
              <img class="avatar" src="${msg.avatar}" />
              <strong>${msg.sender_name}</strong><br>
              <span>${msg.content}</span><br>
              <em>${msg.timestamp}</em>
            </div>`
          );
        });

        $('.inbox-item[data-id="' + messageId + '"]')
          .removeClass('unread')
          .addClass('read')
          .css({ backgroundColor: '#f7f7f7', color: '#000' })
          .find('strong, span, em').css('color', '#000');
      }
    });
  }

  // 📥 Click message block
  $('.inbox-item').on('click', function() {
    const userId = $(this).data('user');
    const messageId = $(this).data('id');
    loadThread(userId, messageId);
  });

  // 🔔 Click bubble opens latest unread
  $('#gradyzer-bubble').on('click', function(e) {
    e.preventDefault();
    const firstUnread = $('.inbox-item.unread').first();
    if (firstUnread.length) {
      firstUnread.trigger('click');
    } else {
      window.location.href = $(this).attr('href');
    }
  });

  // 💬 Send reply
  $('.reply-form').on('submit', function(e) {
    e.preventDefault();
    const userId = $(this).data('user');
    const messageId = $(this).data('message');
    const message = $(this).find('textarea').val();
    const threadBox = $('.message-content');

    $.ajax({
      url: GradyzerSettings.restRoot + 'messages/' + messageId + '/reply',
      method: 'POST',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', GradyzerSettings.nonce);
      },
      data: { message },
      success: function(response) {
        if (response.success) {
          $('.reply-form textarea').val('');
          threadBox.append(
            `<div class="message-block latest-thread">
              <img class="avatar" src="${GradyzerSettings.avatar}" />
              <strong>You</strong><br>
              <span>${message}</span><br>
              <em>Just now</em>
            </div>`
          );
          refreshBubble();
        }
      }
    });
  });

  // 🔄 Refresh bubble
  function refreshBubble() {
    $.get(GradyzerSettings.ajaxUrl, {
      action: 'gradyzer_refresh_bubble'
    }, function(html) {
      $('#gradyzer-bubble').replaceWith(html);
    });
  }

  // 🔄 Refresh thread every 10s
  setInterval(function() {
    if (activeUserId && activeMessageId) {
      loadThread(activeUserId, activeMessageId);
    }
    refreshBubble();
  }, 10000);
});
