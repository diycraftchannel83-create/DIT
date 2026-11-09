package id.ac.darulilmi.portal

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import coil.compose.AsyncImage
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

private val Forest = Color(0xFF075B3D)
private val DeepGreen = Color(0xFF03452F)
private val Emerald = Color(0xFF078653)
private val Mint = Color(0xFFEAF6EF)
private val Lime = Color(0xFFB9E66B)
private val Gold = Color(0xFFE4C45B)
private val Ink = Color(0xFF173B2B)
private val CanvasBg = Color(0xFFF7FAF7)

data class CampusData(
    val name: String = "STIT Darul Ilmi Tasikmalaya",
    val tagline: String = "Membangun Generasi Berilmu dan Berakhlak",
    val about: String = "Portal informasi resmi STIT Darul Ilmi Tasikmalaya. Dapatkan informasi kampus, program studi, berita, dan layanan akademik dalam satu aplikasi.",
    val address: String = "Tasikmalaya, Jawa Barat",
    val heroImageUrl: String = "",
    val programs: List<String> = listOf("S1 Manajemen Pendidikan Islam"),
    val news: List<String> = emptyList()
)

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.statusBarColor = android.graphics.Color.rgb(3, 69, 47)
        window.navigationBarColor = android.graphics.Color.rgb(3, 69, 47)
        setContent {
            MaterialTheme(colorScheme = lightColorScheme(primary = Forest, secondary = Gold, background = CanvasBg, surface = Color.White)) {
                PortalApp()
            }
        }
    }
}

@Composable
private fun PortalApp() {
    var data by remember { mutableStateOf(CampusData()) }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var showLogin by remember { mutableStateOf(false) }
    var username by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var token by remember { mutableStateOf<String?>(null) }
    var role by remember { mutableStateOf("") }
    var userName by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    var selectedNav by remember { mutableIntStateOf(0) }
    val listState = rememberLazyListState()
    val scope = rememberCoroutineScope()

    LaunchedEffect(Unit) {
        loading = true
        try { data = withContext(Dispatchers.IO) { loadPublicHome() }; error = null }
        catch (_: Exception) { error = "Konten kampus belum dapat dimuat. Periksa konfigurasi alamat API." }
        loading = false
    }

    Scaffold(
        containerColor = CanvasBg,
        bottomBar = {
            NavigationBar(containerColor = DeepGreen, contentColor = Color.White, tonalElevation = 8.dp) {
                val labels = listOf("Beranda", "Program Studi", "Berita", "Profil")
                val icons = listOf(Icons.Default.Home, Icons.Default.School, Icons.Default.Article, Icons.Default.Person)
                labels.forEachIndexed { index, label ->
                    NavigationBarItem(
                        selected = selectedNav == index,
                        onClick = {
                            selectedNav = index
                            val target = when(index) { 1 -> 4; 2 -> 6; 3 -> 3; else -> 0 }
                            scope.launch { listState.animateScrollToItem(target) }
                        },
                        icon = { Icon(icons[index], contentDescription = label) },
                        label = { Text(label, fontSize = 10.sp, fontWeight = FontWeight.SemiBold) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = DeepGreen,
                            selectedTextColor = Color.White,
                            indicatorColor = Lime,
                            unselectedIconColor = Color.White.copy(alpha = .75f),
                            unselectedTextColor = Color.White.copy(alpha = .8f)
                        )
                    )
                }
            }
        }
    ) { padding ->
        LazyColumn(
            state = listState,
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = PaddingValues(bottom = 20.dp),
            verticalArrangement = Arrangement.spacedBy(0.dp)
        ) {
            item { HeroHeader(data, token != null, userName, onLogin = { showLogin = true }) }
            item {
                Column(Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 14.dp)) {
                    Button(
                        onClick = { showLogin = true },
                        modifier = Modifier.fillMaxWidth().height(58.dp),
                        shape = RoundedCornerShape(30.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Forest),
                        elevation = ButtonDefaults.buttonElevation(defaultElevation = 4.dp)
                    ) {
                        Icon(Icons.Default.School, null, tint = Color.White, modifier = Modifier.size(27.dp))
                        Spacer(Modifier.width(14.dp))
                        Text(if (token == null) "Login Civitas Akademika" else "Akun: $userName", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                        Spacer(Modifier.weight(1f))
                        Icon(Icons.Default.ChevronRight, null)
                    }
                    Row(Modifier.fillMaxWidth().padding(top = 12.dp), horizontalArrangement = Arrangement.Center) {
                        repeat(5) { index ->
                            Box(Modifier.padding(horizontal = 3.dp).size(if (index == 2) 24.dp else 9.dp, 9.dp).clip(RoundedCornerShape(20.dp)).background(if (index == 2) Forest else Color(0xFFD4E4DA)))
                        }
                    }
                }
            }
            item {
                Row(Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 6.dp), horizontalArrangement = Arrangement.spacedBy(9.dp)) {
                    ShortcutTile("Profil\nKampus", Icons.Default.AccountBalance, Modifier.weight(1f))
                    ShortcutTile("Program\nStudi", Icons.Default.School, Modifier.weight(1f))
                    ShortcutTile("PMB\n(Pendaftaran)", Icons.Default.PersonAddAlt1, Modifier.weight(1f))
                    ShortcutTile("Layanan\nLainnya", Icons.Default.GridView, Modifier.weight(1f))
                }
            }
            item {
                Row(Modifier.fillMaxWidth().padding(start = 20.dp, end = 20.dp, top = 14.dp, bottom = 18.dp), verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        SectionHeading("Tentang Kampus")
                        Text(data.about, color = Color(0xFF34483D), fontSize = 14.sp, lineHeight = 20.sp)
                        TextButton(onClick = { selectedNav = 3 }) {
                            Text("Selengkapnya  ›", color = Forest, fontWeight = FontWeight.Bold)
                        }
                    }
                    Spacer(Modifier.width(10.dp))
                    Box(Modifier.size(112.dp, 132.dp).clip(RoundedCornerShape(topStart = 38.dp, bottomEnd = 38.dp)).background(Brush.linearGradient(listOf(Mint, Lime))), contentAlignment = Alignment.Center) {
                        Icon(Icons.Default.MenuBook, null, tint = Forest, modifier = Modifier.size(58.dp))
                    }
                }
            }
            item {
                Column(Modifier.padding(horizontal = 20.dp)) {
                    SectionHeading("Program Studi")
                    Spacer(Modifier.height(10.dp))
                }
            }
            items(data.programs) { name ->
                Row(Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 4.dp).clip(RoundedCornerShape(18.dp)).background(Color.White).border(BorderStroke(1.dp, Color(0xFFE1EEE5)), RoundedCornerShape(18.dp)), verticalAlignment = Alignment.CenterVertically) {
                    Box(Modifier.padding(12.dp).size(58.dp).clip(RoundedCornerShape(14.dp)).background(Forest), contentAlignment = Alignment.Center) {
                        Icon(Icons.Default.Layers, null, tint = Color.White, modifier = Modifier.size(31.dp))
                    }
                    Column(Modifier.weight(1f).padding(vertical = 12.dp)) {
                        Text(name, color = Color(0xFF182A22), fontWeight = FontWeight.Bold, fontSize = 15.sp)
                        Text("Program studi aktif", color = Color(0xFF64756B), fontSize = 12.sp)
                    }
                    Icon(Icons.Default.ChevronRight, null, tint = Forest, modifier = Modifier.padding(horizontal = 12.dp))
                }
            }
            item {
                Row(Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 12.dp), verticalAlignment = Alignment.CenterVertically) {
                    SectionHeading("Berita dan Pengumuman")
                    Spacer(Modifier.weight(1f))
                    Text("Lihat Semua  ›", color = Forest, fontWeight = FontWeight.Bold, fontSize = 12.sp)
                }
            }
            if (data.news.isEmpty()) {
                item { SimpleInfoCard("Belum ada berita", "Berita yang dipublikasikan Admin akan tampil di sini.") }
            } else {
                items(data.news) { title ->
                    Row(Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 5.dp).clip(RoundedCornerShape(18.dp)).background(Color.White).border(BorderStroke(1.dp, Color(0xFFE1EEE5)), RoundedCornerShape(18.dp)), verticalAlignment = Alignment.CenterVertically) {
                        Box(Modifier.padding(10.dp).size(92.dp, 78.dp).clip(RoundedCornerShape(12.dp)).background(Mint), contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Article, null, tint = Forest, modifier = Modifier.size(34.dp))
                        }
                        Column(Modifier.weight(1f).padding(end = 12.dp, top = 10.dp, bottom = 10.dp)) {
                            Text("Pengumuman", color = Forest, fontSize = 11.sp, fontWeight = FontWeight.Bold)
                            Text(title, color = Ink, fontSize = 14.sp, fontWeight = FontWeight.Bold, lineHeight = 18.sp)
                            Text("Informasi kampus", color = Color.Gray, fontSize = 11.sp)
                        }
                    }
                }
            }
            if (loading) item { LinearProgressIndicator(Modifier.fillMaxWidth().padding(horizontal = 20.dp)) }
            if (error != null) item { Text(error!!, color = MaterialTheme.colorScheme.error, modifier = Modifier.padding(20.dp)) }
            if (message != null) item { Text(message!!, color = Forest, modifier = Modifier.padding(20.dp)) }
            item { SimpleInfoCard("Lokasi Kampus", data.address) }
            if (token != null) {
                item {
                    Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        SectionHeading("Layanan Akun $role")
                        val modules = when (role.lowercase()) {
                            "mahasiswa" -> listOf("KRS", "KHS dan IPK", "Jadwal kuliah", "Nilai", "Tagihan")
                            "dosen" -> listOf("Jadwal mengajar", "Daftar kelas", "Presensi", "Input nilai")
                            else -> listOf("Dashboard akademik", "Laporan program studi", "Ringkasan akademik")
                        }
                        modules.forEach { SimpleInfoCard(it, "Modul prototipe — integrasi data tahap berikutnya") }
                        OutlinedButton(onClick = { token = null; role = ""; userName = ""; message = "Anda telah keluar." }) { Text("Keluar") }
                    }
                }
            }
        }
    }

    if (showLogin) {
        AlertDialog(
            onDismissRequest = { showLogin = false },
            title = { Text("Login civitas akademika") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("Pengunjung publik tidak perlu login. Login hanya untuk mahasiswa, dosen, dan pimpinan.")
                    OutlinedTextField(username, { username = it }, label = { Text("Username") }, singleLine = true)
                    OutlinedTextField(password, { password = it }, label = { Text("Password") }, singleLine = true)
                }
            },
            confirmButton = {
                TextButton(onClick = {
                    loading = true
                    message = null
                    scope.launch {
                        try {
                            val result = withContext(Dispatchers.IO) { login(username.trim(), password) }
                            token = result.first
                            userName = result.second
                            role = result.third
                            showLogin = false
                            password = ""
                            message = "Login berhasil."
                        } catch (e: Exception) { message = e.message ?: "Login gagal." }
                        loading = false
                    }
                }, enabled = username.isNotBlank() && password.isNotBlank() && !loading) { Text("Masuk") }
            },
            dismissButton = { TextButton(onClick = { showLogin = false }) { Text("Batal") } }
        )
    }
}

@Composable
private fun HeroHeader(data: CampusData, loggedIn: Boolean, userName: String, onLogin: () -> Unit) {
    Box(Modifier.fillMaxWidth().height(438.dp).background(Brush.verticalGradient(listOf(DeepGreen, Forest)))) {
        if (data.heroImageUrl.isNotBlank()) {
            AsyncImage(
                model = data.heroImageUrl,
                contentDescription = "Foto gedung STIT Darul Ilmi Tasikmalaya",
                modifier = Modifier.fillMaxWidth().height(270.dp).align(Alignment.BottomEnd).clip(RoundedCornerShape(topStart = 150.dp, bottomStart = 8.dp)),
                contentScale = ContentScale.Crop
            )
            Box(Modifier.fillMaxWidth().height(300.dp).align(Alignment.BottomCenter).background(Brush.verticalGradient(listOf(Color.Transparent, DeepGreen.copy(alpha = .98f)))))
        } else {
            Box(Modifier.fillMaxWidth().height(230.dp).align(Alignment.BottomEnd).background(Brush.linearGradient(listOf(Emerald, DeepGreen))), contentAlignment = Alignment.Center) {
                Icon(Icons.Default.AccountBalance, null, tint = Color.White.copy(alpha = .28f), modifier = Modifier.size(120.dp))
            }
        }
        Column(Modifier.fillMaxSize().padding(horizontal = 22.dp, vertical = 16.dp), verticalArrangement = Arrangement.SpaceBetween) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Image(painterResource(R.drawable.stit_logo), "Logo STIT Darul Ilmi", Modifier.size(70.dp), contentScale = ContentScale.Fit)
                Spacer(Modifier.width(12.dp))
                Text("STIT\nDarul Ilmi\nTasikmalaya", color = Color.White, fontSize = 18.sp, lineHeight = 20.sp, fontWeight = FontWeight.ExtraBold, modifier = Modifier.weight(1f))
                IconButton(onClick = onLogin) { Icon(Icons.Default.Notifications, null, tint = Color.White, modifier = Modifier.size(25.dp)) }
                Button(onClick = onLogin, shape = RoundedCornerShape(28.dp), colors = ButtonDefaults.buttonColors(containerColor = Color.White), contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp)) {
                    Icon(Icons.Default.Person, null, tint = Forest, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(5.dp))
                    Text(if (loggedIn) "Akun" else "Login", color = Forest, fontWeight = FontWeight.Bold)
                }
            }
            Column(Modifier.fillMaxWidth().padding(bottom = 12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("SEKOLAH TINGGI ILMU TARBIYAH", color = Gold, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                Text("DARUL ILMI", color = Color.White, fontSize = 34.sp, lineHeight = 38.sp, fontWeight = FontWeight.Black)
                Text("Tasikmalaya", color = Lime, fontSize = 23.sp, fontWeight = FontWeight.Bold)
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(Modifier.width(4.dp).height(43.dp).clip(RoundedCornerShape(4.dp)).background(Lime))
                    Spacer(Modifier.width(12.dp))
                    Text(data.tagline, color = Color.White, fontSize = 16.sp, lineHeight = 20.sp, fontWeight = FontWeight.Medium)
                }
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(18.dp)) {
                    HeroFeature(Icons.Default.School, "Pendidikan\nBerkualitas")
                    HeroFeature(Icons.Default.MenuBook, "Nilai\nIslami")
                    HeroFeature(Icons.Default.Groups, "Untuk\nUmat")
                }
            }
        }
    }
}

@Composable
private fun HeroFeature(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(5.dp)) {
        Icon(icon, null, tint = Gold, modifier = Modifier.size(24.dp))
        Text(label, color = Color.White, fontSize = 10.sp, lineHeight = 12.sp, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
private fun ShortcutTile(title: String, icon: androidx.compose.ui.graphics.vector.ImageVector, modifier: Modifier) {
    Card(modifier.height(116.dp), shape = RoundedCornerShape(20.dp), colors = CardDefaults.cardColors(containerColor = Color.White), border = BorderStroke(1.dp, Color(0xFFE4EEE7)), elevation = CardDefaults.cardElevation(defaultElevation = 3.dp)) {
        Column(Modifier.fillMaxSize().padding(6.dp), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Center) {
            Icon(icon, null, tint = Forest, modifier = Modifier.size(31.dp))
            Spacer(Modifier.height(8.dp))
            Text(title, color = Ink, fontSize = 10.sp, lineHeight = 13.sp, fontWeight = FontWeight.Bold, textAlign = androidx.compose.ui.text.style.TextAlign.Center)
        }
    }
}

@Composable
private fun SectionHeading(text: String) {
    Column(verticalArrangement = Arrangement.spacedBy(5.dp)) {
        Text(text, fontSize = 21.sp, fontWeight = FontWeight.ExtraBold, color = Forest)
        Box(Modifier.width(88.dp).height(4.dp).clip(RoundedCornerShape(4.dp)).background(Brush.horizontalGradient(listOf(Emerald, Gold))))
    }
}

@Composable
private fun SimpleInfoCard(title: String, subtitle: String) {
    Card(Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 5.dp), shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White), border = BorderStroke(1.dp, Color(0xFFE1EEE5)), elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)) {
        Column(Modifier.fillMaxWidth().padding(15.dp)) {
            Text(title, fontWeight = FontWeight.Bold, color = Ink)
            Spacer(Modifier.height(4.dp))
            Text(subtitle, color = Color(0xFF66796D), fontSize = 13.sp)
        }
    }
}

private fun loadPublicHome(): CampusData {
    val base = BuildConfig.API_BASE_URL.trimEnd('/')
    val json = request("$base/api/v1/public/home", "GET", null, null)
    val campus = json.optJSONObject("campus") ?: JSONObject()
    val programsJson = json.optJSONArray("programs")
    val newsJson = json.optJSONArray("news")
    val programs = (0 until (programsJson?.length() ?: 0)).mapNotNull { i -> programsJson?.optJSONObject(i)?.optString("name") }
    val news = (0 until (newsJson?.length() ?: 0)).mapNotNull { i -> newsJson?.optJSONObject(i)?.optString("title") }
    return CampusData(
        name = campus.optString("name", "STIT Darul Ilmi Tasikmalaya"),
        tagline = campus.optString("tagline").ifBlank { "Membangun Generasi Berilmu dan Berakhlak" },
        about = campus.optString("about").ifBlank { "Portal informasi resmi kampus." },
        address = campus.optString("address").ifBlank { "Tasikmalaya, Jawa Barat" },
        heroImageUrl = campus.optString("hero_image_url"),
        programs = programs.ifEmpty { listOf("S1 Manajemen Pendidikan Islam") },
        news = news
    )
}

private fun login(username: String, password: String): Triple<String, String, String> {
    val base = BuildConfig.API_BASE_URL.trimEnd('/')
    val body = JSONObject().put("username", username).put("password", password)
    val json = request("$base/api/v1/auth/login", "POST", body.toString(), null)
    if (!json.optBoolean("success")) throw IllegalStateException(json.optString("message", "Login gagal."))
    val user = json.optJSONObject("user") ?: JSONObject()
    return Triple(json.getString("token"), user.optString("name"), user.optString("role"))
}

private fun request(url: String, method: String, body: String?, bearer: String?): JSONObject {
    val connection = URL(url).openConnection() as HttpURLConnection
    connection.requestMethod = method
    connection.connectTimeout = 12000
    connection.readTimeout = 12000
    connection.setRequestProperty("Accept", "application/json")
    if (body != null) {
        connection.doOutput = true
        connection.setRequestProperty("Content-Type", "application/json; charset=utf-8")
        connection.outputStream.use { it.write(body.toByteArray(Charsets.UTF_8)) }
    }
    if (bearer != null) connection.setRequestProperty("Authorization", "Bearer $bearer")
    val status = connection.responseCode
    val stream = if (status in 200..299) connection.inputStream else connection.errorStream
    val responseText = stream?.bufferedReader()?.use { it.readText() }.orEmpty()
    connection.disconnect()
    val json = runCatching { JSONObject(responseText) }.getOrElse { JSONObject() }
    if (status !in 200..299) throw IllegalStateException(json.optString("message", "Server merespons HTTP $status"))
    return json
}
