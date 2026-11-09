package id.ac.darulilmi.portal

import android.annotation.SuppressLint
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.ComponentActivity
import androidx.activity.compose.BackHandler
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.viewinterop.AndroidView

private const val CAMPUS_WEBSITE_URL = "https://siakadstitdit.byethost24.com/website/"
private const val CAMPUS_HOST = "siakadstitdit.byethost24.com"
private val CampusGreen = Color(0xFF245C3A)

class MainActivity : ComponentActivity() {
    private var campusWebView: WebView? = null

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.statusBarColor = android.graphics.Color.rgb(36, 92, 58)
        window.navigationBarColor = android.graphics.Color.rgb(36, 92, 58)

        setContent {
            var loading by remember { mutableStateOf(true) }
            var webViewRef by remember { mutableStateOf<WebView?>(null) }

            BackHandler(enabled = webViewRef?.canGoBack() == true) {
                webViewRef?.goBack()
            }

            MaterialTheme(colorScheme = lightColorScheme(primary = CampusGreen)) {
                Box(Modifier.fillMaxSize()) {
                    AndroidView(
                        modifier = Modifier.fillMaxSize(),
                        factory = { context ->
                            WebView(context).apply {
                                campusWebView = this
                                webViewRef = this
                                setBackgroundColor(android.graphics.Color.WHITE)

                                settings.apply {
                                    javaScriptEnabled = true
                                    domStorageEnabled = true
                                    databaseEnabled = true
                                    cacheMode = WebSettings.LOAD_DEFAULT
                                    loadWithOverviewMode = true
                                    useWideViewPort = true
                                    javaScriptCanOpenWindowsAutomatically = false
                                    setSupportMultipleWindows(false)
                                    mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
                                    allowFileAccess = false
                                    allowContentAccess = true
                                }

                                webChromeClient = object : WebChromeClient() {
                                    override fun onProgressChanged(view: WebView?, newProgress: Int) {
                                        loading = newProgress < 100
                                    }
                                }

                                webViewClient = object : WebViewClient() {
                                    override fun shouldOverrideUrlLoading(
                                        view: WebView,
                                        request: WebResourceRequest
                                    ): Boolean {
                                        val uri = request.url
                                        if (uri.scheme == "https" && uri.host == CAMPUS_HOST) {
                                            return false
                                        }
                                        if (uri.scheme == "http" || uri.scheme == "https" ||
                                            uri.scheme == "mailto" || uri.scheme == "tel"
                                        ) {
                                            return try {
                                                startActivity(Intent(Intent.ACTION_VIEW, uri))
                                                true
                                            } catch (_: Exception) {
                                                true
                                            }
                                        }
                                        return true
                                    }

                                    override fun onPageStarted(
                                        view: WebView?,
                                        url: String?,
                                        favicon: android.graphics.Bitmap?
                                    ) {
                                        loading = true
                                    }

                                    override fun onPageFinished(view: WebView?, url: String?) {
                                        loading = false
                                    }
                                }

                                loadUrl(CAMPUS_WEBSITE_URL)
                            }
                        },
                        update = { view ->
                            if (webViewRef !== view) webViewRef = view
                        }
                    )

                    if (loading) {
                        CircularProgressIndicator(
                            modifier = Modifier.align(Alignment.Center),
                            color = CampusGreen
                        )
                    }
                }
            }
        }
    }

    @Deprecated("Deprecated in Java")
    override fun onBackPressed() {
        val view = campusWebView
        if (view?.canGoBack() == true) view.goBack() else super.onBackPressed()
    }

    override fun onDestroy() {
        campusWebView?.apply {
            stopLoading()
            webChromeClient = null
            webViewClient = WebViewClient()
            destroy()
        }
        campusWebView = null
        super.onDestroy()
    }
}
