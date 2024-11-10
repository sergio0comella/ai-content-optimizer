jQuery(document).ready(function ($) {
  $("#analyze-content").on("click", function () {
    const contentContainer = document.querySelector(
      ".is-root-container.is-desktop-preview"
    );

    let fullContent = "";
    if (contentContainer) {
      const paragraphs = contentContainer.querySelectorAll("p");
      let contentArray = [];
      paragraphs.forEach((paragraph) => {
        contentArray.push(paragraph.innerText);
      });
      fullContent = contentArray.join("\n\n");
    } else {
      console.log("Content container not found.");
    }

    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "ai_optimize_content",
        content: fullContent,
      },
      success: function (response) {
        // Display the suggestions as HTML
        $("#ai-content-suggestions").html(
          "<strong>Suggestions:</strong><br>" + response
        );

        // Add a button to view suggestions in a new tab
        if ($("#view-suggestions").length === 0) {
          $("<button>")
            .attr("id", "view-suggestions")
            .text("Open in new tab")
            .css({
              display: "block",
              marginTop: "15px",
              padding: "10px",
              backgroundColor: "#0073aa",
              color: "#fff",
              border: "none",
              borderRadius: "5px",
              cursor: "pointer",
            })
            .appendTo("#ai-content-suggestions")
            .on("click", function () {
              viewHTMLContent(response);
            });
        }
      },
      error: function () {
        $("#ai-content-suggestions").html("Error retrieving suggestions.");
      },
    });
  });

  // Function to open HTML content in a new tab with styling
  function viewHTMLContent(htmlContent) {
    // Create a new window and write HTML content
    const newWindow = window.open("", "_blank");
    newWindow.document.write(`
            <html>
            <head>
                <title>AI Content Suggestions</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                        line-height: 1.6;
                        color: #333;
                        background-color: #f9f9f9;
                    }
                    h1 {
                        color: #0073aa;
                        font-size: 24px;
                        margin-bottom: 20px;
                    }
                    .suggestions-content {
                        max-width: 800px;
                        margin: 0 auto;
                        background: #fff;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    }
                    .suggestions-content h2, .suggestions-content h3 {
                        color: #0073aa;
                    }
                    .suggestions-content p {
                        margin: 15px 0;
                    }
                    pre {
                        background: #f4f4f4;
                        padding: 10px;
                        border-radius: 4px;
                        overflow: auto;
                    }
                    code {
                        background: #f4f4f4;
                        padding: 2px 4px;
                        border-radius: 4px;
                    }
                    a {
                        color: #0073aa;
                        text-decoration: none;
                    }
                    a:hover {
                        text-decoration: underline;
                    }
                </style>
            </head>
            <body>
                <div class="suggestions-content">
                    <h1>AI Content Suggestions</h1>
                    ${htmlContent}
                </div>
            </body>
            </html>
        `);
    newWindow.document.close();
  }
});
