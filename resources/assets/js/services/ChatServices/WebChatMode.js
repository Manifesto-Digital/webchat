import axios from "axios";
import isSkip from "../../mixins/isSkip";

const moment = require("moment-timezone");

let WebChatMode = function() {
  this.name = "webchat";
  this.dataLayerEventName = 'message_sent_to_chatbot';
};

WebChatMode.prototype.sendRequest = function(message, webChatComponent) {
    // Add author and typing message on send if typingIndicatorOnSend is set to true and this is not a url click
    if (webChatComponent.typingIndicatorOnSend && message.type !== "url_click") {
      if (webChatComponent.useBotName || webChatComponent.useBotAvatar) {
        const authorMsg = webChatComponent.newAuthorMessage(message, true)
        webChatComponent.messageList.push(authorMsg)
      }

      let typingMessage = {
        author: 'them',
        type: 'typing',
        mode: webChatComponent.modeData.mode,
        data: {
          animate: webChatComponent.messageAnimation
        }
      }
      webChatComponent.messageList.push(typingMessage)
    }

    if (
      message.type === "chat_open" ||
      message.type === "url_click" ||
      message.type === "trigger" ||
      message.type === "form_response" ||
      message.type === "long_text_edit" ||
      message.data.text.length > 0
    ) {
      // Make a copy of the message to send to the backend.
      // This is needed so that the author change will not affect this.messageList.
      const msgCopy = Object.assign({}, message);

      // Set the message author ID.
      msgCopy.author = msgCopy.user_id;

      const webchatMessage = {
        notification: "message",
        user_id: msgCopy.user_id,
        author: msgCopy.author,
        message_id: msgCopy.id,
        content: msgCopy
      };

      // Need to add error handling here
      return axios.post("/incoming/webchat", webchatMessage);
    } else {
      return new Promise((resolve, reject) => resolve(null));
    }
};

function onWebchatMessageReceived (message, referrerUrl, webChatComponent) {
  if (window.openDialogWebchat && window.openDialogWebchat.hooks && window.openDialogWebchat.hooks.onWebchatMessageReceived) {
    window.openDialogWebchat.hooks.onWebchatMessageReceived(message, referrerUrl, webChatComponent);
  }
}

function sendMessageReceivedEvent (message, webChatComponent) {
  let referrerUrl = '';
  if (window.self !== window.top) {
    referrerUrl = document.referrer.match(/^.+:\/\/[^\/]+/)[0];
  } else {
    referrerUrl = document.location.origin;
  }

  onWebchatMessageReceived(message, referrerUrl, webChatComponent);

  window.parent.postMessage(
    { dataLayerEvent: { event: 'message_received_from_chatbot', message: message.intent } },
    referrerUrl
  )
}

function sendMetaDataEvent (data) {
  let referrerUrl = '';
  if (window.self !== window.top) {
    referrerUrl = document.referrer.match(/^.+:\/\/[^\/]+/)[0];
  } else {
    referrerUrl = document.location.origin;
  }

  window.parent.postMessage(
    { meta: data },
    referrerUrl
  )
}

/*
 * If the message is set to hide avatar, look back 2 messages and if that is an avatar, remove it
 */
function removeAvatar (webChatComponent, message) {
  if (webChatComponent.typingIndicatorOnSend && message.data && message.data.hideavatar) {
    const avatarMessage = webChatComponent.messageList[webChatComponent.messageList.length - 2]
    if (avatarMessage.type === 'author') {
      webChatComponent.messageList.splice(webChatComponent.messageList.length - 2, 1)
    }
  }
}

/*
 * Logic for adding author and typing message based on whether they have already been added and the current message
 */
function authorAndTypingMessage (webChatComponent, message) {
  if ((webChatComponent.useBotName || webChatComponent.useBotAvatar) && !message.data.hideavatar) {
    if (!webChatComponent.typingIndicatorOnSend) { // Only add an author message if we haven't already
      const authorMsg = webChatComponent.newAuthorMessage(message)
      webChatComponent.messageList.push(authorMsg)
    }
  }

  // Only add typing message if one has not been added on send
  if (!webChatComponent.typingIndicatorOnSend) {
    const typingMessage = {
      author: 'them',
      type: 'typing',
      mode: webChatComponent.modeData.mode,
      data: {
        animate: webChatComponent.messageAnimation
      }
    }
    webChatComponent.messageList.push(typingMessage)
    return typingMessage
  } else {
    // Otherwise, fetch the previous message as the typing message
    return webChatComponent.messageList[webChatComponent.messageList.length - 1]
  }
}

/*
 * For non-messages, clean up the typing and author messages that were turned on when sending
 */
function removeTypingAndAvatar (webChatComponent) {
  let previousMessage = webChatComponent.messageList[webChatComponent.messageList.length - 1]
  if (previousMessage.type === 'typing') {
    webChatComponent.messageList.splice(webChatComponent.messageList.length - 1, 1)
  }

  previousMessage = webChatComponent.messageList[webChatComponent.messageList.length - 1]
  if (previousMessage.type === 'author') {
    webChatComponent.messageList.splice(webChatComponent.messageList.length - 1, 1)
  }
}

WebChatMode.prototype.sendResponseSuccess = function(response, sentMessage, webChatComponent) {
  return new Promise((resolve, reject) => {
    sendMetaDataEvent(response.data.meta);

    let messages = response.data.messages;

    if (messages instanceof Array) {
      let index = 0;
      let totalMessages = messages.filter(msg => msg !== false).length;
      let typingMessage;
      let clearCtaText = true;

      messages.forEach((message, i) => {
        const messageIndex = index;

        // If the message hides avatar, and we added one on send, go back and remove it
        if (i === 0) {
          removeAvatar(webChatComponent, message)
        }

        if (message && message.type === "cta") {
          if (clearCtaText) {
            webChatComponent.ctaText = [];
            clearCtaText = false;
          }
          if (webChatComponent.ctaText.length === 2) {
            webChatComponent.ctaText.splice(0, 1);
          }
          webChatComponent.ctaText.push(message.data.text);

          totalMessages -= 1;
        } else if (message.type === 'meta') {
          webChatComponent.updateMessageMetaData(message);

          totalMessages -= 1;
        } else if (!message) {
          webChatComponent.contentEditable = true;
          if (messageIndex >= totalMessages -1) {
            resolve(webChatComponent.messageList)
          }

          if (webChatComponent.typingIndicatorOnSend) {
            removeTypingAndAvatar(webChatComponent)
          }

        } else {
          if (messageIndex === 0) {
            typingMessage = authorAndTypingMessage(webChatComponent, message)
          }

          setTimeout(() => {
            webChatComponent.$emit("newMessage", message);

            /* eslint-disable no-param-reassign */
            message.data.animate = webChatComponent.messageAnimation;

            if (message.data) {
              if (messageIndex === 0 && totalMessages > 1) {
                message.data.first = true;
              }

              if (messageIndex > 0 && messageIndex < totalMessages - 1) {
                message.data.middle = true;
              }

              if (messageIndex > 0 && messageIndex === totalMessages - 1) {
                message.data.last = true;
              }
            }

            if (
              messageIndex === 0 ||
              !webChatComponent.hideTypingIndicatorOnInternalMessages
            ) {
              typingMessage.type = message.type;
              typingMessage.data = message.data;

              webChatComponent.$root.$emit("scroll-down-message-list");
              setTimeout(() => {
                webChatComponent.$root.$emit("scroll-down-message-list");
              }, 50);
            } else {
              if (messageIndex > 0 && messageIndex === totalMessages - 1) {
                /* eslint-disable no-param-reassign */
                message.data.lastInternal = true;
              }

              message.mode = webChatComponent.modeData.mode;
              webChatComponent.messageList.push(message);
            }

            if (message.data) {
              webChatComponent.contentEditable = !message.data.disable_text;
            }

            if (message.type === "fp-form") {
              webChatComponent.showFullPageFormInputMessage(message);
            }

            if (message.type === "fp-rich") {
              webChatComponent.showFullPageRichInputMessage(message);
            }

            if (message.type === "long_text") {
              webChatComponent.showLongTextInputMessage(message);
            }

            if (message.type !== "fp-form" && message.type !== "fp-rich" && message.type !== "long_text") {
              webChatComponent.showFullPageFormInput = false;
              webChatComponent.showFullPageRichInput = false;
              webChatComponent.showLongTextInput = false;
              webChatComponent.showMessages = true;
            }

            if (!webChatComponent.hideTypingIndicatorOnInternalMessages) {
              if (messageIndex < totalMessages - 1 && isSkip(message) !== 'skip') {
                webChatComponent.$nextTick(() => {
                  webChatComponent.$nextTick(() => {
                    typingMessage = {
                      author: "them",
                      type: "typing",
                      mode: webChatComponent.modeData.mode,
                      data: {
                        animate: webChatComponent.messageAnimation
                      }
                    };
                    webChatComponent.messageList.push(typingMessage);
                  });
                });
              }
            }

            if (messageIndex >= totalMessages -1) {
              resolve(webChatComponent.messageList)
            }
          }, (messageIndex + 1) * webChatComponent.messageDelay);

          sendMessageReceivedEvent(message, webChatComponent);

          index += 1;
        }
      });

    } else if (messages) {
      const message = messages;

      removeAvatar(webChatComponent, message)

      if (sentMessage.type === "chat_open") {
        if (message && message.data) {
          let typingMessage;

          typingMessage = authorAndTypingMessage(webChatComponent, message)

          setTimeout(() => {
            webChatComponent.$emit("newMessage", message);

            message.data.animate = webChatComponent.messageAnimation;

            typingMessage.type = message.type;
            typingMessage.data = message.data;

            if (message.type === "fp-form") {
              webChatComponent.showFullPageFormInputMessage(message);
            }

            if (message.type === "fp-rich") {
              webChatComponent.showFullPageRichInputMessage(message);
            }

            if (message.type === "long_text") {
              webChatComponent.showLongTextInputMessage(message);
            }

            webChatComponent.contentEditable = !message.data.disable_text;

            resolve(webChatComponent.messageList)
          }, webChatComponent.messageDelay);
        } else {
          // If we don't get data about whether to disable the editor, turn it on
          webChatComponent.contentEditable = true;
        }
        sendMessageReceivedEvent(message, webChatComponent);
      } else {
        let typingMessage;

        if (message.data) {
          typingMessage = authorAndTypingMessage(webChatComponent, message)
        }

        setTimeout(() => {
          // Only add a message to the list if it is a message object
          if (typeof message === "object" && message !== null) {
            webChatComponent.$emit("newMessage", message);

            message.data.animate = webChatComponent.messageAnimation;

            typingMessage.type = message.type;
            typingMessage.data = message.data;

            webChatComponent.$root.$emit("scroll-down-message-list");
            setTimeout(() => {
              webChatComponent.$root.$emit("scroll-down-message-list");
            }, 50);
          }

          if (message.data) {
            webChatComponent.contentEditable = !message.data.disable_text;
          }

          if (message.type === 'cta') {
            let authorMessage = webChatComponent.messageList[webChatComponent.messageList.length - 2]
            if (webChatComponent.typingIndicatorOnSend && authorMessage.type === 'author') {
              // Delete author message if there is one
              webChatComponent.messageList.splice(webChatComponent.messageList.length - 2, 1);
            }
          }

          if (message.type === "fp-form") {
            webChatComponent.showFullPageFormInputMessage(message);
          }

          if (message.type === "fp-rich") {
            webChatComponent.showFullPageRichInputMessage(message);
          }

          if (message.type === "long_text") {
            webChatComponent.showLongTextInputMessage(message);
          }

          if (message.type !== "fp-form" && message.type !== "fp-rich" && message.type !== "long_text") {
            webChatComponent.showFullPageFormInput = false;
            webChatComponent.showFullPageRichInput = false;
            webChatComponent.showLongTextInput = false;
            webChatComponent.showMessages = true;
          }

          resolve(webChatComponent.messageList)
        }, webChatComponent.messageDelay);
        sendMessageReceivedEvent(message, webChatComponent);
      }
    }
  })
};

WebChatMode.prototype.sendResponseError = function(error, sentMessage, webChatComponent) {
  const message = {
    type: "text",
    author: "them",
    data: {
      date: moment()
        .tz("UTC")
        .format("ddd D MMM"),
      time: moment()
        .tz("UTC")
        .format("hh:mm:ss A"),
      text: "We're sorry, that didn't work, please try again"
    }
  };

  if (webChatComponent.useBotName || webChatComponent.useBotAvatar) {
    const authorMsg = webChatComponent.newAuthorMessage(message);
    webChatComponent.messageList.push(authorMsg);
  }

  let typingMessage = {
    author: "them",
    type: "typing",
    mode: webChatComponent.modeData.mode,
    data: {
      animate: webChatComponent.messageAnimation
    }
  };
  webChatComponent.messageList.push(typingMessage);

  setTimeout(() => {
    typingMessage.type = message.type;
    typingMessage.data = message.data;

    webChatComponent.$root.$emit("scroll-down-message-list");
  }, webChatComponent.messageDelay);
};

WebChatMode.prototype.sendTypingRequest = function(message, webChatComponent) {
  return Promise.resolve();
};

WebChatMode.prototype.sendTypingResponseSuccess = function(response, webChatComponent) {
  return Promise.resolve();
};

WebChatMode.prototype.sendTypingResponseError = function(error, webChatComponent) {
  return Promise.resolve();
};

WebChatMode.prototype.initialiseChat = function(webChatComponent) {
  webChatComponent.contentEditable = true;
  webChatComponent.chatbotAvatar = webChatComponent.chatbotAvatarPath;
  return Promise.resolve();
};

WebChatMode.prototype.destroyChat = function(webChatComponent) {
  return Promise.resolve();
};

WebChatMode.prototype.postDestroyChat = function(oldModeData, webChatComponent) {
  return Promise.resolve();
};

WebChatMode.prototype.setModeInstance = function(number) {
  this.modeInstance = number;
  return Promise.resolve();
};

WebChatMode.prototype.getDataLayerEventName = function () {
  return this.dataLayerEventName;
};

export default WebChatMode;
