package com.sawaedarab.employee;

import android.Manifest;
import android.app.Activity;
import android.app.DownloadManager;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.ViewGroup;
import android.webkit.CookieManager;
import android.webkit.GeolocationPermissions;
import android.webkit.PermissionRequest;
import android.webkit.SslErrorHandler;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.net.http.SslError;
import android.widget.Toast;

public final class MainActivity extends Activity {
    private static final String APP_URL = "https://ss.sawaedarab.com/index.php?mobile=employee";
    private static final String ALLOWED_HOST = "ss.sawaedarab.com";
    private static final int FILE_CHOOSER_REQUEST = 4101;
    private static final int WEB_PERMISSION_REQUEST = 4102;
    private static final int GEOLOCATION_REQUEST = 4103;
    private WebView webView;
    private ValueCallback<Uri[]> fileCallback;
    private PermissionRequest pendingWebPermission;
    private GeolocationPermissions.Callback pendingGeoCallback;
    private String pendingGeoOrigin;

    @Override protected void onCreate(Bundle state) {
        super.onCreate(state);
        webView = new WebView(this);
        webView.setLayoutParams(new ViewGroup.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT));
        setContentView(webView);
        configureWebView();
        if (state == null) webView.loadUrl(APP_URL); else webView.restoreState(state);
    }

    private void configureWebView() {
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setGeolocationEnabled(true);
        settings.setAllowFileAccess(false);
        settings.setAllowContentAccess(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        settings.setUserAgentString(settings.getUserAgentString() + " SawaedEmployee/1.11 AndroidWebView");
        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, false);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) WebView.setWebContentsDebuggingEnabled(false);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) settings.setSafeBrowsingEnabled(true);

        webView.setWebViewClient(new WebViewClient() {
            @Override public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) { return route(request.getUrl()); }
            @Override public boolean shouldOverrideUrlLoading(WebView view, String url) { return route(Uri.parse(url)); }
            @Override public void onReceivedSslError(WebView view, SslErrorHandler handler, SslError error) { handler.cancel(); Toast.makeText(MainActivity.this,"تعذر التحقق من شهادة الاتصال الآمن",Toast.LENGTH_LONG).show(); }
            @Override public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) { if(request.isForMainFrame())Toast.makeText(MainActivity.this,"تعذر الاتصال بالنظام. تحقق من الإنترنت ثم أعد المحاولة.",Toast.LENGTH_LONG).show(); }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override public void onPermissionRequest(PermissionRequest request) {
                if (!isAllowed(request.getOrigin())) { request.deny(); return; }
                pendingWebPermission=request;
                if (checkSelfPermission(Manifest.permission.CAMERA)!=PackageManager.PERMISSION_GRANTED) requestPermissions(new String[]{Manifest.permission.CAMERA},WEB_PERMISSION_REQUEST);
                else grantAllowedWebResources(request);
            }
            @Override public void onGeolocationPermissionsShowPrompt(String origin, GeolocationPermissions.Callback callback) {
                if (!isAllowed(Uri.parse(origin))) { callback.invoke(origin,false,false); return; }
                if(checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION)==PackageManager.PERMISSION_GRANTED)callback.invoke(origin,true,false);
                else { pendingGeoOrigin=origin;pendingGeoCallback=callback;requestPermissions(new String[]{Manifest.permission.ACCESS_FINE_LOCATION,Manifest.permission.ACCESS_COARSE_LOCATION},GEOLOCATION_REQUEST); }
            }
            @Override public boolean onShowFileChooser(WebView view, ValueCallback<Uri[]> callback, FileChooserParams params) {
                if(fileCallback!=null)fileCallback.onReceiveValue(null);fileCallback=callback;
                Intent intent=params.createIntent();intent.addCategory(Intent.CATEGORY_OPENABLE);
                try{startActivityForResult(intent,FILE_CHOOSER_REQUEST);return true;}catch(ActivityNotFoundException e){fileCallback=null;Toast.makeText(MainActivity.this,"لا يوجد تطبيق لاختيار الملف",Toast.LENGTH_LONG).show();return false;}
            }
        });

        webView.setDownloadListener((url,userAgent,contentDisposition,mimeType,length)->{
            if(!isAllowed(Uri.parse(url))){openExternal(Uri.parse(url));return;}
            DownloadManager.Request request=new DownloadManager.Request(Uri.parse(url));request.setMimeType(mimeType);request.addRequestHeader("Cookie",CookieManager.getInstance().getCookie(url));request.addRequestHeader("User-Agent",userAgent);request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);((DownloadManager)getSystemService(DOWNLOAD_SERVICE)).enqueue(request);
        });
    }

    private boolean route(Uri uri){ if(isAllowed(uri))return false;openExternal(uri);return true; }
    private boolean isAllowed(Uri uri){ return uri!=null&&"https".equalsIgnoreCase(uri.getScheme())&&ALLOWED_HOST.equalsIgnoreCase(uri.getHost()); }
    private void openExternal(Uri uri){ try{startActivity(new Intent(Intent.ACTION_VIEW,uri));}catch(Exception e){Toast.makeText(this,"تعذر فتح الرابط",Toast.LENGTH_SHORT).show();} }
    private String[] allowedWebResources(String[] requested){ java.util.ArrayList<String> out=new java.util.ArrayList<>();for(String item:requested)if(PermissionRequest.RESOURCE_VIDEO_CAPTURE.equals(item))out.add(item);return out.toArray(new String[0]); }
    private void grantAllowedWebResources(PermissionRequest request){String[] allowed=allowedWebResources(request.getResources());if(allowed.length>0)request.grant(allowed);else request.deny();}

    @Override public void onRequestPermissionsResult(int requestCode,String[] permissions,int[] results){
        super.onRequestPermissionsResult(requestCode,permissions,results);
        boolean granted=results.length>0&&results[0]==PackageManager.PERMISSION_GRANTED;
        if(requestCode==WEB_PERMISSION_REQUEST&&pendingWebPermission!=null){if(granted)grantAllowedWebResources(pendingWebPermission);else pendingWebPermission.deny();pendingWebPermission=null;}
        if(requestCode==GEOLOCATION_REQUEST&&pendingGeoCallback!=null){pendingGeoCallback.invoke(pendingGeoOrigin,granted,false);pendingGeoCallback=null;pendingGeoOrigin=null;}
    }

    @Override protected void onActivityResult(int requestCode,int resultCode,Intent data){super.onActivityResult(requestCode,resultCode,data);if(requestCode==FILE_CHOOSER_REQUEST&&fileCallback!=null){Uri[] result=null;if(resultCode==RESULT_OK&&data!=null){if(data.getClipData()!=null){int n=data.getClipData().getItemCount();result=new Uri[n];for(int i=0;i<n;i++)result[i]=data.getClipData().getItemAt(i).getUri();}else if(data.getData()!=null)result=new Uri[]{data.getData()};}fileCallback.onReceiveValue(result);fileCallback=null;}}
    @Override protected void onSaveInstanceState(Bundle out){webView.saveState(out);super.onSaveInstanceState(out);}
    @Override public void onBackPressed(){if(webView.canGoBack())webView.goBack();else super.onBackPressed();}
    @Override protected void onDestroy(){if(webView!=null){webView.loadUrl("about:blank");webView.stopLoading();webView.setWebChromeClient(null);webView.setWebViewClient(null);webView.destroy();}super.onDestroy();}
}
