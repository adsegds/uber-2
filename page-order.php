 <!-- Chart.js（グラフ用） -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Firebase SDK -->
  <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-storage-compat.js"></script>

  <script>
    // =============================
    // 0) オーナーログイン（パスワード＋15時間制限）
    // =============================
    (function () {
      // ★ここにパスワードを書く（好きな文字列に変えてOK）
      const OWNER_PASSWORD = "nanbu";

      const SESSION_LIMIT_MS = 15 * 60 * 60 * 1000; // 15時間
      const SESSION_KEY = "lw_ownerSession";

      function isSessionValid() {
        const raw = localStorage.getItem(SESSION_KEY);
        if (!raw) return false;
        try {
          const data = JSON.parse(raw);
          if (!data || !data.loggedAt) return false;
          const loggedAt = data.loggedAt;
          const now = Date.now();
          return now - loggedAt < SESSION_LIMIT_MS;
        } catch (e) {
          return false;
        }
      }

      function saveSession() {
        const payload = {
          loggedAt: Date.now()
        };
        localStorage.setItem(SESSION_KEY, JSON.stringify(payload));
      }

            window.addEventListener("DOMContentLoaded", () => {
        const loginWrap = document.getElementById("ownerLogin");
        const appWrap = document.getElementById("ownerApp");
        const pwdInput = document.getElementById("ownerPasswordInput");
        const loginBtn = document.getElementById("ownerLoginBtn");
        const errorLabel = document.getElementById("ownerLoginError");
        const storeIdInput = document.getElementById("storeIdInput");

        if (!loginWrap || !appWrap || !pwdInput || !loginBtn) return;

        function showApp() {
          loginWrap.style.display = "none";
          appWrap.style.display = "block";
        }

        function showLogin() {
          loginWrap.style.display = "block";
          appWrap.style.display = "none";
        }

        // すでにセッションが有効なら、ログイン画面スキップ
        const currentStoreId = localStorage.getItem("lw_ownerStoreId");
        if (currentStoreId && ALLOWED_STORES.includes(currentStoreId) && isSessionValid()) {
          // ついでに入力欄も埋めておく
          if (storeIdInput) storeIdInput.value = currentStoreId;
          showApp();
        } else {
          showLogin();
        }

        function tryLogin() {
          const pwd = pwdInput.value.trim();
          const storeId = storeIdInput.value.trim();

          if (!storeId) {
            errorLabel.textContent = "店舗IDを入力してください。";
            return;
          }
          if (!ALLOWED_STORES.includes(storeId)) {
            errorLabel.textContent = "店舗IDが違います。";
            return;
          }

          if (!pwd) {
            errorLabel.textContent = "パスワードを入力してください。";
            return;
          }

          if (pwd === OWNER_PASSWORD) {
            // 店舗IDを保存
            localStorage.setItem("lw_ownerStoreId", storeId);

            saveSession();
            errorLabel.textContent = "";

            // ★ storeId を反映させるためにページごと再読み込み
            location.reload();
          } else {
            errorLabel.textContent = "パスワードが違います。";
          }
        }

        loginBtn.addEventListener("click", tryLogin);
        pwdInput.addEventListener("keydown", (e) => {
          if (e.key === "Enter") {
            tryLogin();
          }
        });
      });
    })();


    // =============================
    // Firebase 設定
    // =============================
    const firebaseConfig = {
      apiKey: "AIzaSyAPBMvTpzKCcPxLETncBqVR8fzf0cqKirc",
      authDomain: "lawson-workflow.firebaseapp.com",
      projectId: "lawson-workflow",
      storageBucket: "lawson-workflow.firebasestorage.app",
      messagingSenderId: "335371795694",
      appId: "1:335371795694:web:3b0950a616d368b284c7ff",
      measurementId: "G-BGENZNZMRC"
    };

    firebase.initializeApp(firebaseConfig);
    const db = firebase.firestore();



    

// ★ 店舗ID（storeId）はログイン画面で localStorage に保存しておく想定
const ALLOWED_STORES = ["nambucho"];               // 許可する店舗ID
const STORE_KEY = "lw_ownerStoreId";

// ローカルストレージから店舗IDを取得
const STORE_ID = localStorage.getItem(STORE_KEY);

// 有効な店舗IDのときだけ storeRef を作る
let storeRef = null;
if (STORE_ID && ALLOWED_STORES.includes(STORE_ID)) {
  storeRef = db.collection("stores").doc(STORE_ID);
}


    // 6:00切り替えの「今日の営業日」
    function getCurrentBusinessKey() {
      const now = new Date();
      const y = now.getFullYear();
      const m = now.getMonth();
      const d = now.getDate();
      const base = new Date(y, m, d);
      if (now.getHours() < 7) {
        base.setDate(base.getDate() - 1);
      }
      const yy = base.getFullYear();
      const mm = String(base.getMonth() + 1).padStart(2, "0");
      const dd = String(base.getDate()).padStart(2, "0");
      return `${yy}-${mm}-${dd}`;
    }

    // date input の値（YYYY-MM-DD） → businessKey
    function businessKeyFromDateInput(dateStr) {
      if (!dateStr) return null;
      return dateStr;
    }

    document.addEventListener("DOMContentLoaded", () => {
    // =============================
  // AI最適化（Cloud Functions 経由版）
  // =============================
  const aiBtn = document.getElementById("aiOptimizeBtn");
  const shiftaiPrimaryBtn = document.getElementById("shiftaiPrimaryBtn");
  const shiftaiPreviewArea = document.getElementById("shiftaiPreviewArea");
  const shiftaiPreviewEmpty = document.getElementById("shiftaiPreviewEmpty");
  const shiftaiBaseDateInput = document.getElementById("shiftaiBaseDate");

  // ★ Firebase Functions の URL（ターミナルに出てたやつ）
  const AI_FUNCTION_URL =
    "https://us-central1-lawson-workflow.cloudfunctions.net/generateShift";

  async function runShiftAiTest() {
    if (!shiftaiPreviewArea || !shiftaiPrimaryBtn) return;

    const oldLabel = shiftaiPrimaryBtn.textContent;
    shiftaiPrimaryBtn.disabled = true;
    shiftaiPrimaryBtn.textContent = "AIに問い合わせ中…";

    try {
      // 週 / 月 & 基準日
      const checked = document.querySelector(
        'input[name="shiftaiRangeType"]:checked'
      );
      const mode = checked ? checked.value : "week";
      const baseDate = shiftaiBaseDateInput.value || getCurrentBusinessKey();

      // ★ まずはテスト用ダミーデータ（業種共通のサンプル）
      //   → あとで Firestore から staff / requiredSlots を読む形に差し替える
      const payload = {
        targetDate: baseDate,               // Cloud Functions 側の targetDate
        storeId: STORE_ID || "test-store",  // Cloud Functions 側の storeId

        // 汎用スタッフ（どの業種でも違和感ないサンプル）
        staff: [
          {
            id: "staff001",
            name: "スタッフA",
            roles: ["基本業務"],
            maxHoursPerWeek: 28,
            canNight: true,
          },
          {
            id: "staff002",
            name: "スタッフB",
            roles: ["基本業務"],
            maxHoursPerWeek: 20,
            canNight: false,
          },
          {
            id: "staff003",
            name: "スタッフC",
            roles: ["基本業務", "サポート業務"],
            maxHoursPerWeek: 24,
            canNight: true,
          },
        ],

        // 汎用時間帯（9〜18時の昼メイン業種でも使える感じ）
        requiredSlots: [
          { timeRange: "09:00-12:00", minStaff: 1 },
          { timeRange: "12:00-15:00", minStaff: 2 },
          { timeRange: "15:00-18:00", minStaff: 2 },
        ],
      };

      console.log("🔥 Cloud Functions に送るデータ:", payload);

      const res = await fetch(AI_FUNCTION_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      if (!res.ok) {
        const text = await res.text();
        throw new Error("generateShift エラー: " + text);
      }

      const data = await res.json();
      console.log("✅ generateShift 応答:", data);

      // ---- 画面に表示（とりあえず JSON をそのまま表示）----
      if (shiftaiPreviewEmpty) {
        shiftaiPreviewEmpty.style.display = "none";
      }
      shiftaiPreviewArea.innerHTML = "";

      const card = document.createElement("div");
      card.style.whiteSpace = "pre-wrap";
      card.style.fontSize = "12px";
      card.textContent = JSON.stringify(data, null, 2);

      shiftaiPreviewArea.appendChild(card);
    } catch (err) {
      console.error(err);
      alert("AI呼び出しでエラーが起きました: " + (err.message || err));
    } finally {
      shiftaiPrimaryBtn.disabled = false;
      shiftaiPrimaryBtn.textContent = oldLabel;
    }
  }

  // メインのボタンに紐づけ
  if (shiftaiPrimaryBtn) {
    shiftaiPrimaryBtn.addEventListener("click", (e) => {
      e.preventDefault();
      runShiftAiTest();
    });
  }

  // 右上の黒い「AI最適化」ボタン → 同じ処理を叩く
  if (aiBtn) {
    aiBtn.addEventListener("click", (e) => {
      e.preventDefault();
      runShiftAiTest();
    });
  }


  // storeId がまだ設定されていない場合は、Firestore系の初期化はスキップ
  if (!storeRef) {
    console.warn("STORE_ID が未設定のため、Firestoreの処理はまだ実行しません。ログイン後にページを再読み込みしてください。");
    return;
  }





   // =============================
  // ② reports（スタッフ → 経営者）一覧
  // =============================
  const adminReportsList  = document.getElementById("adminReportsList");
  const adminReportsEmpty = document.getElementById("adminReportsEmpty");

  // 日付フォーマット（Timestamp → "YYYY/MM/DD HH:MM"）
  function formatReportDate(ts) {
    if (!ts || !ts.toDate) return "";
    const d = ts.toDate();
    const yyyy = d.getFullYear();
    const mm   = String(d.getMonth() + 1).padStart(2, "0");
    const dd   = String(d.getDate()).padStart(2, "0");
    const hh   = String(d.getHours()).padStart(2, "0");
    const mi   = String(d.getMinutes()).padStart(2, "0");
    return `${yyyy}/${mm}/${dd} ${hh}:${mi}`;
  }

  if (adminReportsList) {
    storeRef
      .collection("reports")
      .orderBy("createdAt", "desc")
      .limit(100)
      .onSnapshot(
        (snap) => {
          adminReportsList.innerHTML = "";

          if (snap.empty) {
            if (adminReportsEmpty) adminReportsEmpty.style.display = "block";
            return;
          }
          if (adminReportsEmpty) adminReportsEmpty.style.display = "none";

          snap.forEach((doc) => {
            const data = doc.data() || {};

            const cat    = data.category || "その他";
            const msg    = (data.message || "").replace(/\n/g, "<br>");
            const name   = data.staffName || "（不明）";
            const status = data.status || "未対応";
            const dateText = data.createdAt
              ? formatReportDate(data.createdAt)
              : "";

            const item = document.createElement("div");
            item.className = "report-item";

            item.innerHTML = `
              <div class="report-header">
                <span class="badge">カテゴリ: ${cat}</span>
                <span class="badge badge-status">${status}</span>
              </div>
              <div class="report-body">${msg}</div>
              <div class="report-meta">
                <span>送信者：${name}</span>
                <span>${dateText}</span>
              </div>
              <div class="report-actions">
                <button type="button"
                        class="btn-report-small js-report-done">
                  対応済みにする
                </button>
                <button type="button"
                        class="btn-report-small btn-report-delete js-report-delete">
                  削除
                </button>
              </div>
            `;

            // ▼ ボタンのイベント設定
            const doneBtn   = item.querySelector(".js-report-done");
            const deleteBtn = item.querySelector(".js-report-delete");

            // 「対応済みにする」ボタン
            doneBtn.addEventListener("click", async () => {
              try {
                await storeRef
                  .collection("reports")
                  .doc(doc.id)
                  .update({
                    status: "対応済み",
                    updatedAt: firebase.firestore.FieldValue.serverTimestamp(),
                  });
              } catch (err) {
                console.error("status 更新エラー:", err);
                alert("ステータス更新に失敗しました。");
              }
            });

            // 「削除」ボタン
            deleteBtn.addEventListener("click", async () => {
              const ok = window.confirm("この報告を削除してもよろしいですか？");
              if (!ok) return;

              try {
                await storeRef
                  .collection("reports")
                  .doc(doc.id)
                  .delete();
              } catch (err) {
                console.error("reports 削除エラー:", err);
                alert("削除に失敗しました。");
              }
            });

            adminReportsList.appendChild(item);
          });
        },
        (err) => {
          console.error("reports 読み込みエラー:", err);
          if (adminReportsEmpty) {
            adminReportsEmpty.textContent = "報告の読み込みに失敗しました。";
            adminReportsEmpty.style.display = "block";
          }
        }
      );
  }





      

  // =============================
  // シフトAIビュー用のUI制御（週 / 月切り替え）
  // =============================
  (function () {
    const rangeRadios = document.querySelectorAll('input[name="shiftaiRangeType"]');
    const dateInput = document.getElementById("shiftaiBaseDate");
    const dateLabel = document.querySelector("[data-shiftai-date-label]");
    const primaryBtnLabel = document.getElementById("shiftaiPrimaryBtnLabel");

    if (!rangeRadios.length || !dateInput || !dateLabel || !primaryBtnLabel) {
      return; // まだ shiftai ビューを作っていない場合は何もしない
    }

    // 初期値：今日の営業日を基準日にセット
    if (!dateInput.value) {
      dateInput.value = getCurrentBusinessKey(); // "YYYY-MM-DD"
    }

    function updateUI() {
      const checked = document.querySelector('input[name="shiftaiRangeType"]:checked');
      const mode = checked ? checked.value : "week";

      if (mode === "week") {
        dateLabel.textContent = "基準日";
        primaryBtnLabel.textContent = "この週をAIで最適化";
      } else {
        dateLabel.textContent = "基準月";
        primaryBtnLabel.textContent = "この月をAIで最適化";
      }
    }

    rangeRadios.forEach((r) => {
      r.addEventListener("change", updateUI);
    });

    updateUI();
  })();



      // =============================
      // オーナーID / 端末名の保存＆自動セット
      // =============================
      (function () {
        const OWNER_ID_KEY = "lw_ownerOwnerId";
        const ownerIdInput = document.getElementById("ownerIdInput");
        if (!ownerIdInput) return;

        const savedOwnerId = localStorage.getItem(OWNER_ID_KEY);
        if (savedOwnerId) {
          ownerIdInput.value = savedOwnerId;
        }

        ownerIdInput.addEventListener("change", () => {
          const value = ownerIdInput.value.trim();
          if (value) {
            localStorage.setItem(OWNER_ID_KEY, value);
          }
        });
      })();

      // =============================
      // モバイル用メニュー開閉
      // =============================
      const sidebar = document.querySelector(".sidebar");
      const sidebarOverlay = document.getElementById("sidebarOverlay");
      const mobileMenuBtn = document.getElementById("mobileMenuBtn");

      function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove("mobile-open");
        if (sidebarOverlay) sidebarOverlay.classList.remove("active");
      }

      function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add("mobile-open");
        if (sidebarOverlay) sidebarOverlay.classList.add("active");
      }

      mobileMenuBtn?.addEventListener("click", () => {
        if (sidebar.classList.contains("mobile-open")) {
          closeSidebar();
        } else {
          openSidebar();
        }
      });

      sidebarOverlay?.addEventListener("click", closeSidebar);

     // =============================
// 左メニュー切り替え
// =============================
const navButtons = document.querySelectorAll(".sidebar-nav button");
const views = document.querySelectorAll(".view-panel");

// Analytics 用参照（activateView 内で使うので先に宣言だけ）
let runAnalyticsRef = null;
let analyticsDateInputRef = null;

function activateView(viewId) {
  // ビューパネルの表示切り替え
  views.forEach((v) =>
    v.classList.toggle("active", v.id === "view-" + viewId)
  );

  // サイドバーの active 切り替え
  navButtons.forEach((btn) =>
    btn.classList.toggle("active", btn.dataset.view === viewId)
  );

  // シフト表タブが開かれたら現在設定を読み込む
  if (viewId === "shift") {
    loadShiftImageSetting();
  }

  // グラフ分析タブが開かれたら自動で集計（初回）
  if (viewId === "analytics" && runAnalyticsRef) {
    if (analyticsDateInputRef && !analyticsDateInputRef.value) {
      analyticsDateInputRef.value = getCurrentBusinessKey();
    }
    runAnalyticsRef();
  }

 // ★ AIボタン制御（ここを追加）
  const aiBtn = document.getElementById("aiOptimizeBtn");
  if (aiBtn) {
    if (viewId === "shiftai") {
      aiBtn.style.display = "block";
    } else {
      aiBtn.style.display = "none";
    }
  }
  
}

// 左メニューボタンにクリックイベントを付与
navButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const viewId = btn.dataset.view;

    // ★ お知らせセンターだけは別タブで notice.html を開く
    if (viewId === "notice-center") {
      window.open("notice.html", "_blank"); // 同じフォルダにある想定
      closeSidebar();                       // スマホならメニュー閉じる
      return;                               // ここで終了
    }

    // それ以外のメニューは普通にビュー切り替え
    activateView(viewId);
    closeSidebar();
  });
});

// デフォルト：お知らせ管理ビューを表示
activateView("news");


      // =============================
      // 1) ownerNews （お知らせ管理）
      // =============================
      const newsTitleInput = document.getElementById("newsTitle");
      const newsBodyInput = document.getElementById("newsBody");
      const newsAddBtn = document.getElementById("newsAddBtn");
      const newsClearBtn = document.getElementById("newsClearFormBtn");
      const newsStatus = document.getElementById("newsStatus");
      const newsList = document.getElementById("newsList");
      const ownerNewsColRef = storeRef.collection("ownerNews");

      function setNewsStatus(msg, ok) {
  newsStatus.textContent = msg || "";
  newsStatus.className = "status " + (ok ? "ok" : "err");
}


      newsAddBtn.addEventListener("click", async () => {
        const title = newsTitleInput.value.trim();
        const body = newsBodyInput.value.trim();
        if (!title && !body) {
          setNewsStatus("タイトルか本文を入力してください。", false);
          return;
        }
        setNewsStatus("保存中…", true);
        try {
          await ownerNewsColRef.add({
            title,
            body,
            createdAt: firebase.firestore.FieldValue.serverTimestamp()
          });
          setNewsStatus("お知らせを追加しました。", true);
          newsTitleInput.value = "";
          newsBodyInput.value = "";
        } catch (e) {
          console.error(e);
          setNewsStatus("保存に失敗しました。", false);
        }
      });

      newsClearBtn.addEventListener("click", () => {
        newsTitleInput.value = "";
        newsBodyInput.value = "";
        setNewsStatus("", true);
      });

      ownerNewsColRef
        .orderBy("createdAt", "desc")
        .limit(20)
        .onSnapshot(
          (snapshot) => {
            newsList.innerHTML = "";
            if (snapshot.empty) {
              const li = document.createElement("li");
              li.textContent = "現在、お知らせはありません。";
              newsList.appendChild(li);
              return;
            }
            snapshot.forEach((doc) => {
              const data = doc.data();
              const li = document.createElement("li");
              li.className = "news-item";

              const main = document.createElement("div");
              main.className = "news-main";

              const titleEl = document.createElement("div");
              titleEl.className = "news-title";
              titleEl.textContent = data.title || "(タイトルなし)";

              const bodyEl = document.createElement("div");
              bodyEl.className = "news-body";
              bodyEl.textContent = data.body || "";

              const metaEl = document.createElement("div");
              metaEl.className = "news-meta";
              const createdAt = data.createdAt ? data.createdAt.toDate() : null;
              metaEl.textContent = createdAt
                ? createdAt.toLocaleString("ja-JP")
                : "日時情報なし";

              main.appendChild(titleEl);
              main.appendChild(bodyEl);
              main.appendChild(metaEl);

              const actions = document.createElement("div");
              actions.className = "news-actions";
              const delBtn = document.createElement("button");
              delBtn.className = "btn-danger";
              delBtn.textContent = "削除";
              delBtn.addEventListener("click", async () => {
                if (!confirm("このお知らせを削除しますか？")) return;
                await doc.ref.delete();
              });
              actions.appendChild(delBtn);

              li.appendChild(main);
              li.appendChild(actions);

              newsList.appendChild(li);
            });
          },
          (error) => {
            console.error("ownerNews 読み込みエラー", error);
            newsList.innerHTML = "";
            const li = document.createElement("li");
            li.textContent = "お知らせの読み込みに失敗しました。";
            newsList.appendChild(li);
          }
        );

           // =============================
      // 2) taskSlots（時間帯ごとのタスク定義）
      // =============================
      const slotSelect = document.getElementById("slotSelect");
      const tasksPhotoInput = document.getElementById("tasksPhotoInput"); // ★ 写真必須
      const tasksAInput = document.getElementById("tasksAInput");         // 必須
      const tasksBInput = document.getElementById("tasksBInput");         // 余裕あれば
      const reloadSlotBtn = document.getElementById("reloadSlotBtn");
      const saveSlotBtn = document.getElementById("saveSlotBtn");
      const taskStatus = document.getElementById("taskStatus");
      const taskSlotsRef = storeRef.collection("taskSlots");
      const tasksDocRef  = storeRef.collection("tasks").doc("main"); // チェック状態リセット用

      // ★ 時間帯設定 UI 要素
      const slotConfigStartInput = document.getElementById("slotConfigStart");
      const slotConfigEndInput = document.getElementById("slotConfigEnd");
      const slotConfigIntervalSelect = document.getElementById("slotConfigInterval");
      const slotConfigGenerateBtn = document.getElementById("slotConfigGenerateBtn");

      // Firestore 上の時間帯設定ドキュメント
      const timeSlotsConfigRef = storeRef.collection("settings").doc("timeSlots");

      // ★ カスタム時間帯テキスト用の要素
      const slotCustomInput     = document.getElementById("slotCustomInput");
      const slotCustomLoadBtn   = document.getElementById("slotCustomLoadBtn");
      const slotCustomSaveBtn   = document.getElementById("slotCustomSaveBtn");

      // デフォルトの時間帯リスト（いままでと同じ）
      function getDefaultSlots() {
        return [
          { id: "t6-8",   label: "6:00〜8:00（朝の立ち上げ）" },
          { id: "t9-11",  label: "9:00〜11:00（午前帯）" },
          { id: "t12-14", label: "12:00〜14:00（昼帯）" },
          { id: "t15-17", label: "15:00〜17:00（夕方前）" },
          { id: "t18-20", label: "18:00〜20:00（夕方〜夜）" },
          { id: "t21-23", label: "21:00〜23:00（夜帯）" },
          { id: "t0-2",   label: "0:00〜2:00（深夜前半）" },
          { id: "t3-5",   label: "3:00〜5:00（早朝）" }
        ];
      }

      // ★ 現在の slots をカスタムテキストに反映
      function updateSlotCustomTextarea(slots) {
        if (!slotCustomInput) return;
        const text = (slots && slots.length > 0)
          ? slots.map((s) => s._raw || s.label || s.id).join("\n")
          : "";
        slotCustomInput.value = text;
      }

      // slotSelect の option を作り直す
      function renderSlotOptions(slots, initialLoad) {
        slotSelect.innerHTML = "";
        if (!slots || slots.length === 0) {
          slots = getDefaultSlots();
        }

        slots.forEach((s) => {
          const opt = document.createElement("option");
          opt.value = s.id;

          let text = "";

          // ★ カスタム編集で入力した行には _raw が入っている
          // 例: "06:00-09:00 朝の立ち上げ"
          if (s._raw && typeof s._raw === "string") {
            // 先頭の時間部分とラベル部分に分割
            const parts = s._raw.split(/\s+/, 2);
            const rangePart = parts[0];          // "06:00-09:00"
            const labelPart = parts[1] || "";    // "朝の立ち上げ"（ない場合もあり）

            // 06:00-09:00 → 06:00〜09:00 にしてから結合
            const rangeNice = rangePart.replace("-", "〜");
            text = labelPart ? `${rangeNice} ${labelPart}` : rangeNice;
          } else {
            // デフォルトのスロット（label に時間も入っているやつ）はそのまま
            text = s.label || s.id;
          }

          opt.textContent = text;
          slotSelect.appendChild(opt);
        });

        // 1個目を選択
        if (!slotSelect.value && slots[0]) {
          slotSelect.value = slots[0].id;
        }

        // 選択された時間帯の定義を読み込む
        if (slotSelect.value) {
          loadSlotDefinition(slotSelect.value);
        }
      }

      // Firestore から時間帯設定を読み込み
      async function loadTimeSlotsConfig(initialLoad) {
        try {
          const snap = await timeSlotsConfigRef.get();
          if (!snap.exists) {
            // まだ設定ない → デフォルトで描画
            const defaults = getDefaultSlots();
            updateSlotCustomTextarea(defaults);
            renderSlotOptions(defaults, initialLoad);
            return;
          }
          const data = snap.data() || {};
          const slots = Array.isArray(data.slots) ? data.slots : getDefaultSlots();

          // UI 側に start/end/interval 反映
          if (slotConfigStartInput && data.start) {
            slotConfigStartInput.value = data.start;
          }
          if (slotConfigEndInput && data.end) {
            slotConfigEndInput.value = data.end;
          }
          if (slotConfigIntervalSelect && data.intervalMinutes) {
            const val = String(data.intervalMinutes);
            const has = Array.from(slotConfigIntervalSelect.options).some(
              (opt) => opt.value === val
            );
            if (has) slotConfigIntervalSelect.value = val;
          }

          // ★ textarea にも反映
          updateSlotCustomTextarea(slots);
          renderSlotOptions(slots, initialLoad);
        } catch (e) {
          console.error("timeSlotsConfig 読み込みエラー", e);
          const defaults = getDefaultSlots();
          updateSlotCustomTextarea(defaults);
          renderSlotOptions(defaults, initialLoad);
        }
      }

      // 時刻文字列 "HH:MM" → 分（0〜）に変換
      function parseTimeToMinutes(hm) {
        const [hStr, mStr] = hm.split(":");
        const h = parseInt(hStr, 10);
        const m = parseInt(mStr, 10);
        if (
          Number.isNaN(h) ||
          Number.isNaN(m) ||
          h < 0 || h > 24 ||
          m < 0 || m >= 60
        ) {
          return null;
        }
        return (h * 60 + m) % (24 * 60); // 0〜1439 に丸める
      }

      // 分（0〜） → "HH:MM" に変換
      function formatMinutesToTime(total) {
        const t = ((total % (24 * 60)) + (24 * 60)) % (24 * 60);
        const h = String(Math.floor(t / 60)).padStart(2, "0");
        const m = String(t % 60).padStart(2, "0");
        return `${h}:${m}`;
      }

      // 入力値から時間帯スロットを自動生成（開始〜終了＋区切り・分ベース）
      function generateSlotsFromInputs() {
        if (!slotConfigStartInput || !slotConfigEndInput || !slotConfigIntervalSelect) {
          return null;
        }

        const startStr = slotConfigStartInput.value || "06:00";
        const endStr   = slotConfigEndInput.value   || "23:00";
        const intervalMinutes =
          parseInt(slotConfigIntervalSelect.value, 10) || 60; // 15 / 30 / 60 / 120...

        const startMin = parseTimeToMinutes(startStr);
        const endMin   = parseTimeToMinutes(endStr);

        if (startMin === null || endMin === null) {
          alert("時間の形式が正しくありません（例: 06:00）");
          return null;
        }
        if (!intervalMinutes || intervalMinutes <= 0) {
          alert("区切り時間（分）が不正です");
          return null;
        }

        // 1日をまたぐ場合（終了 <= 開始 のときは翌日扱い）
        let rangeEnd = endMin;
        if (rangeEnd <= startMin) {
          rangeEnd += 24 * 60;
        }

        const slots = [];
        let cur = startMin;
        let index = 0;
        const MAX_SLOTS = 96; // 15分刻みで最大 24h

        while (cur < rangeEnd && index < MAX_SLOTS) {
          const next = Math.min(cur + intervalMinutes, rangeEnd);

          const sLabel = formatMinutesToTime(cur);   // 例: "06:00"
          const eLabel = formatMinutesToTime(next);  // 例: "06:30"
          const label  = `${sLabel}〜${eLabel}`;

          // slotId はユニークなら何でもOK（index.html 側も prefix として使うだけ）
          const id = `slot_${sLabel.replace(":", "")}_${eLabel.replace(":", "")}`;

          slots.push({
            id,
            label,
            start: sLabel,
            end: eLabel,
            order: index
          });

          cur = next;
          index++;
        }

        return {
          slots,
          start: startStr,
          end:   endStr,
          intervalMinutes
        };
      }

      // ★ カスタムテキストから slots 配列を作る
      function parseCustomSlotsFromTextarea() {
        if (!slotCustomInput) return null;

        const lines = slotCustomInput.value.split("\n");
        const slots = [];
        let lineNo = 0;
        let hasMinuteError = false;

        for (const rawLine of lines) {
          lineNo++;
          const line = rawLine.trim();
          if (!line) continue;

          // 最初の空白までが時間レンジ、それ以降がラベル
          const parts = line.split(/\s+/);
          const rangePart = parts[0];
          const labelRest = parts.slice(1).join(" ");

          const rangeSplit = rangePart.split("-");
          if (rangeSplit.length !== 2) {
            alert(`行${lineNo}：「${line}」の時間の書式が不正です。例）06:00-09:00 のように入力してください。`);
            return null;
          }

          function parseTime(str) {
            const [hStr, mStr] = str.split(":");
            const h = Number(hStr);
            const m = mStr !== undefined ? Number(mStr) : 0;
            if (
              Number.isNaN(h) ||
              Number.isNaN(m) ||
              h < 0 ||
              h > 24 ||
              m < 0 ||
              m >= 60
            ) {
              return null;
            }
            if (m !== 0) {
              hasMinuteError = true;
            }
            return { hour: h % 24, minute: m };
          }

          const start = parseTime(rangeSplit[0]);
          const end   = parseTime(rangeSplit[1]);
          if (!start || !end) {
            alert(`行${lineNo}：「${line}」の時間が読み取れませんでした。例）06:00-09:00 の形式で入力してください。`);
            return null;
          }

          const h1 = start.hour;
          const h2 = end.hour;

          const id = `t${h1}-${h2}`;
          const label =
            labelRest && labelRest.trim().length > 0
              ? labelRest.trim()
              : `${String(h1).padStart(2, "0")}:00〜${String(h2).padStart(2, "0")}:00`;

          slots.push({
            id,
            label,
            _raw: `${String(h1).padStart(2, "0")}:00-${String(h2).padStart(2, "0")}:00 ${label}`
          });
        }

        if (slots.length === 0) {
          alert("有効な行がありませんでした。");
          return null;
        }

        if (hasMinuteError) {
          alert("分が00以外の行がありました。分はすべて00として扱いました。");
        }

        return slots;
      }

      // 「この設定で時間帯を自動生成」ボタン
      slotConfigGenerateBtn?.addEventListener("click", async () => {
        const result = generateSlotsFromInputs();
        if (!result) return;
        const { slots, start, end, intervalMinutes } = result;

        try {
          await timeSlotsConfigRef.set(
            { start, end, intervalMinutes, slots },
            { merge: true }
          );
          renderSlotOptions(slots, false);
          updateSlotCustomTextarea(slots);
          setTaskStatus(
            "時間帯リストを更新しました。このあと各時間帯のタスクを編集してください。",
            true
          );
        } catch (e) {
          console.error("timeSlotsConfig 保存エラー", e);
          setTaskStatus("時間帯リストの保存に失敗しました。", false);
        }
      });

      // 「現在の時間帯をテキストに展開」
      slotCustomLoadBtn?.addEventListener("click", async () => {
        try {
          const snap = await timeSlotsConfigRef.get();
          let slots;
          if (snap.exists) {
            const data = snap.data() || {};
            slots = Array.isArray(data.slots) ? data.slots : getDefaultSlots();
          } else {
            slots = getDefaultSlots();
          }
          updateSlotCustomTextarea(slots);
          setTaskStatus("現在の時間帯をテキストに展開しました。", true);
        } catch (e) {
          console.error("slotCustomLoad エラー", e);
          setTaskStatus("時間帯の読み込みに失敗しました。", false);
        }
      });

      // 「このテキスト内容で時間帯を更新」
      slotCustomSaveBtn?.addEventListener("click", async () => {
        const slots = parseCustomSlotsFromTextarea();
        if (!slots) return;

        try {
          await timeSlotsConfigRef.set(
            { slots },   // カスタムなので start/end/intervalMinutes は省略
            { merge: true }
          );
          renderSlotOptions(slots, false);
          updateSlotCustomTextarea(slots);
          setTaskStatus(
            "カスタム時間帯でリストを更新しました。このあと各時間帯のタスクを設定してください。",
            true
          );
        } catch (e) {
          console.error("slotCustomSave エラー", e);
          setTaskStatus("カスタム時間帯の保存に失敗しました。", false);
        }
      });

      function setTaskStatus(msg, ok) {
        taskStatus.textContent = msg || "";
        taskStatus.className = "status " + (ok ? "ok" : "err");
      }

      async function loadSlotDefinition(slotId) {
        setTaskStatus("読み込み中…", true);
        if (tasksPhotoInput) tasksPhotoInput.value = "";
        tasksAInput.value = "";
        tasksBInput.value = "";
        try {
          const snap = await taskSlotsRef.doc(slotId).get();
          if (!snap.exists) {
            setTaskStatus(
              "まだ定義されていません。HTML側の初期値が使われます。",
              true
            );
            return;
          }
          const data = snap.data() || {};

          // ★ 互換用：新旧どっちでも読めるようにする
          const photoTasks   = data.photoTasks || [];
          const requiredTasks = data.requiredTasks || data.tasksA || [];
          const optionalTasks = data.optionalTasks || data.tasksB || [];

          if (tasksPhotoInput) {
            photoTasks.forEach((t, i) => {
              tasksPhotoInput.value += (i ? "\n" : "") + t;
            });
          }
          requiredTasks.forEach((t, i) => {
            tasksAInput.value += (i ? "\n" : "") + t;
          });
          optionalTasks.forEach((t, i) => {
            tasksBInput.value += (i ? "\n" : "") + t;
          });

          setTaskStatus("読み込み完了。", true);
        } catch (e) {
          console.error(e);
          setTaskStatus("読み込みに失敗しました。", false);
        }
      }

      reloadSlotBtn.addEventListener("click", () => {
        loadSlotDefinition(slotSelect.value);
      });

      slotSelect.addEventListener("change", () => {
        loadSlotDefinition(slotSelect.value);
      });

      saveSlotBtn.addEventListener("click", async () => {
        const slotId = slotSelect.value;

        const photoTasks = tasksPhotoInput
          ? tasksPhotoInput.value
              .split("\n")
              .map((t) => t.trim())
              .filter((t) => t)
          : [];

        const requiredTasks = tasksAInput.value
          .split("\n")
          .map((t) => t.trim())
          .filter((t) => t);

        const optionalTasks = tasksBInput.value
          .split("\n")
          .map((t) => t.trim())
          .filter((t) => t);

        setTaskStatus("保存中…", true);
        try {
          await taskSlotsRef.doc(slotId).set(
            {
              photoTasks,
              requiredTasks,
              optionalTasks,
              // ★ 互換用：index.html がまだ tasksA/B を参照していても動くように
              tasksA: requiredTasks,
              tasksB: optionalTasks,
            },
            { merge: true }
          );

          // この時間帯のチェック状態だけリセット
          const docSnap = await tasksDocRef.get();
          const data = docSnap.exists ? docSnap.data() : {};
          const checkedIds = (data.checkedIds || []).filter(
            (id) => !id.startsWith(slotId + "-")
          );
          await tasksDocRef.set(
            { checkedIds, businessKey: getCurrentBusinessKey() },
            { merge: true }
          );

          setTaskStatus(
            "保存しました（この時間帯のチェック状態をリセットしました）。",
            true
          );
        } catch (e) {
          console.error(e);
          setTaskStatus("保存に失敗しました。", false);
        }
      });

      // 初回ロード（時間帯設定 → プルダウン生成 → textarea反映）
      loadTimeSlotsConfig(true);




      // =============================
      // 3) handoverTemplates（引き継ぎメモ用ボタン）
      // =============================
      const handoverChipsInput = document.getElementById("handoverChipsInput");
      const handoverReloadBtn = document.getElementById("handoverReloadBtn");
      const handoverSaveBtn = document.getElementById("handoverSaveBtn");
      const handoverStatus = document.getElementById("handoverStatus");
      const handoverPreview = document.getElementById("handoverPreview");
      const handoverTplRef = storeRef.collection("handoverTemplates").doc("global");

      function setHandoverStatus(msg, ok) {
        handoverStatus.textContent = msg || "";
        handoverStatus.className = "status " + (ok ? "ok" : "err");
      }

      function updateHandoverPreview(chips) {
        handoverPreview.innerHTML = "";
        if (!chips || chips.length === 0) {
          const span = document.createElement("span");
          span.className = "handover-preview-chip";
          span.textContent = "（ボタンなし）";
          handoverPreview.appendChild(span);
          return;
        }
        chips.forEach((text) => {
          const chip = document.createElement("span");
          chip.className = "handover-preview-chip";
          chip.textContent = text;
          handoverPreview.appendChild(chip);
        });
      }

      async function loadHandoverTemplate() {
        setHandoverStatus("読み込み中…", true);
        try {
          const snap = await handoverTplRef.get();
          const chips =
            snap.exists && Array.isArray(snap.data().chips)
              ? snap.data().chips
              : [];
          handoverChipsInput.value = chips.join("\n");
          updateHandoverPreview(chips);
          setHandoverStatus("読み込み完了。", true);
        } catch (e) {
          console.error(e);
          setHandoverStatus("読み込みに失敗しました。", false);
        }
      }

      handoverReloadBtn.addEventListener("click", loadHandoverTemplate);

      handoverSaveBtn.addEventListener("click", async () => {
        const chips = handoverChipsInput.value
          .split("\n")
          .map((t) => t.trim())
          .filter((t) => t);
        setHandoverStatus("保存中…", true);
        try {
          await handoverTplRef.set({ chips }, { merge: true });
          updateHandoverPreview(chips);
          setHandoverStatus("保存しました。", true);
        } catch (e) {
          console.error(e);
          setHandoverStatus("保存に失敗しました。", false);
        }
      });

      loadHandoverTemplate();

      // =============================
      // 4) shiftSheet（シフト表設定）
      // =============================
      const shiftImageUrlInput = document.getElementById("shiftImageUrlInput");
      const shiftImageUrlSaveBtn = document.getElementById("shiftImageUrlSaveBtn");
      const shiftImagePreview = document.getElementById("shiftImagePreview");
      const shiftStatus = document.getElementById("shiftStatus");
      const shiftDocRef = storeRef.collection("settings").doc("shiftSheet");

      // Storage の参照を用意
      const storage = firebase.storage();
      const shiftImageFileInput = document.getElementById("shiftImageFileInput");

      // 画像アップロード → Storage → Firestore → 画面反映
      shiftImageFileInput?.addEventListener("change", async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        try {
          setShiftStatus("画像をアップロード中…", true);

          // 保存先パス（ファイル名の前にタイムスタンプを付けて被り防止）
          const storageRef = storage
            .ref()
            .child(`shiftSheets/${Date.now()}_${file.name}`);

          // Storage にアップロード
          const snapshot = await storageRef.put(file);

          // 公開URLを取得
          const url = await snapshot.ref.getDownloadURL();

          // Firestore に保存（settings/shiftSheet.imageUrl）
          await shiftDocRef.set({ imageUrl: url }, { merge: true });

          // 画面も更新
          if (shiftImageUrlInput) {
            shiftImageUrlInput.value = url;
          }
          if (shiftImagePreview) {
            shiftImagePreview.src = url;
            shiftImagePreview.style.display = "block";
          }

          setShiftStatus("アップロード＆URL保存が完了しました。", true);
        } catch (err) {
          console.error("シフト表画像アップロードエラー", err);
          setShiftStatus(
            "画像のアップロードに失敗しました：" + (err.message || ""),
            false
          );
        } finally {
          // 同じファイルをもう一回選べるようにする
          if (shiftImageFileInput) {
            shiftImageFileInput.value = "";
          }
        }
      });

      function setShiftStatus(msg, ok) {
        if (!shiftStatus) return;
        shiftStatus.textContent = msg || "";
        shiftStatus.className = "status " + (ok ? "ok" : "err");
      }

      // Firestore から現在のURLを読み込む
      async function loadShiftImageSetting() {
        if (!shiftImageUrlInput || !shiftImagePreview) return;
        setShiftStatus("読み込み中…", true);

        try {
          const snap = await shiftDocRef.get();
          if (!snap.exists) {
            shiftImageUrlInput.value = "";
            shiftImagePreview.style.display = "none";
            setShiftStatus("まだシフト表は設定されていません。", true);
            return;
          }

          const data = snap.data() || {};
          const url = data.imageUrl || "";

          shiftImageUrlInput.value = url;
          if (url) {
            shiftImagePreview.src = url;
            shiftImagePreview.style.display = "block";
            setShiftStatus("読み込み完了。", true);
          } else {
            shiftImagePreview.style.display = "none";
            setShiftStatus("URLが設定されていません。", true);
          }
        } catch (e) {
          console.error("shiftSheet 読み込みエラー", e);
          setShiftStatus(
            "読み込みに失敗しました：" + (e.message || ""),
            false
          );
        }
      }

      // 保存ボタン（URL欄の内容をそのまま保存）
      shiftImageUrlSaveBtn?.addEventListener("click", async () => {
        if (!shiftImageUrlInput) return;
        const url = shiftImageUrlInput.value.trim();

        setShiftStatus("保存中…", true);
        try {
          await shiftDocRef.set({ imageUrl: url || null }, { merge: true });

          if (url) {
            shiftImagePreview.src = url;
            shiftImagePreview.style.display = "block";
          } else if (shiftImagePreview) {
            shiftImagePreview.style.display = "none";
          }

          setShiftStatus("シフト表URLを保存しました。", true);
        } catch (e) {
          console.error("shiftSheet 保存エラー", e);
          setShiftStatus(
            "保存に失敗しました：" + (e.message || ""),
            false
          );
        }
      });

      // （おまけ）オーナー画面を開いた直後にも一回だけ読み込んでおく
      loadShiftImageSetting();

      // =============================
      // 5) checklistLogs（スタッフログ）
      // =============================
      const logDateInput = document.getElementById("logDate");
      const logDeviceInput = document.getElementById("logDevice");
      const logKeywordInput = document.getElementById("logKeyword");
      const logFilterBtn = document.getElementById("logFilterBtn");
      const logTableBody = document.getElementById("logTableBody");
      const logEmptyMsg = document.getElementById("logEmptyMsg");
      const logStatus = document.getElementById("logStatus");
      const logsColRef = storeRef.collection("checklistLogs");
      let logsUnsub = null;

      function setLogStatus(msg, ok) {
        logStatus.textContent = msg || "";
        logStatus.className = "status " + (ok ? "ok" : "err");
      }

      function renderLogs(docs, businessKey, deviceFilter) {
        logTableBody.innerHTML = "";
        logEmptyMsg.style.display = "none";

        if (docs.length === 0) {
          const tr = document.createElement("tr");
          const td = document.createElement("td");
          td.colSpan = 5;
          td.textContent = "該当するログはありませんでした。";
          tr.appendChild(td);
          logTableBody.appendChild(tr);

          logEmptyMsg.textContent =
            "営業日: " +
            (businessKey || "指定なし") +
            (deviceFilter ? " / 端末: " + deviceFilter : "");
          logEmptyMsg.style.display = "block";
          return;
        }

        docs.forEach((doc) => {
          const data = doc.data();
          const tr = document.createElement("tr");

          const createdAt = data.createdAt
            ? data.createdAt.toDate()
            : data.clientTimeISO
            ? new Date(data.clientTimeISO)
            : null;

          const timeTd = document.createElement("td");
          timeTd.textContent = createdAt
            ? createdAt.toLocaleTimeString("ja-JP", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit"
              })
            : "";

          const bkTd = document.createElement("td");
          bkTd.textContent = data.businessKey || "";

          const devTd = document.createElement("td");
          devTd.textContent = data.deviceLabel || "未設定";

          const actionTd = document.createElement("td");
          actionTd.textContent = data.action || "";

          const taskTd = document.createElement("td");
          if (data.taskLabel) {
            taskTd.textContent = data.taskLabel;
          } else if (data.extra && data.extra.noteText) {
            taskTd.textContent = data.extra.noteText;
          } else {
            taskTd.textContent = "";
          }

          tr.appendChild(timeTd);
          tr.appendChild(bkTd);
          tr.appendChild(devTd);
          tr.appendChild(actionTd);
          tr.appendChild(taskTd);

          logTableBody.appendChild(tr);
        });
      }

      function startLogsListener() {
        const dateStr = logDateInput.value;
        const businessKey = businessKeyFromDateInput(dateStr);
        const deviceFilter = logDeviceInput.value.trim();
        const keywordRaw = logKeywordInput ? logKeywordInput.value.trim() : "";
        const keyword = keywordRaw.toLowerCase();

        if (!businessKey) {
          const todayKey = getCurrentBusinessKey();
          logDateInput.value = todayKey;
          return startLogsListener();
        }

        if (logsUnsub) {
          logsUnsub();
          logsUnsub = null;
        }

        setLogStatus("読み込み中…", true);
        logTableBody.innerHTML =
          "<tr><td colspan='5'>ログを読み込み中...</td></tr>";
        logEmptyMsg.style.display = "none";

        logsUnsub = logsColRef
          .where("businessKey", "==", businessKey)
          .onSnapshot(
            (snapshot) => {
              let docs = [];
              snapshot.forEach((doc) => docs.push(doc));

              // createdAt 降順ソート
              docs.sort((a, b) => {
                const da = a.data().createdAt
                  ? a.data().createdAt.toMillis()
                  : 0;
                const db = b.data().createdAt
                  ? b.data().createdAt.toMillis()
                  : 0;
                return db - da;
              });

              // ① 端末ラベルでフィルタ
              if (deviceFilter) {
                docs = docs.filter(
                  (doc) => (doc.data().deviceLabel || "") === deviceFilter
                );
              }

              // ② キーワードで全文検索フィルタ
              if (keyword) {
                docs = docs.filter((doc) => {
                  const data = doc.data() || {};

                  const createdAt = data.createdAt
                    ? data.createdAt.toDate()
                    : data.clientTimeISO
                    ? new Date(data.clientTimeISO)
                    : null;

                  const timeStr = createdAt
                    ? createdAt.toLocaleTimeString("ja-JP", {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                      })
                    : "";

                  const taskText = data.taskLabel
                    ? data.taskLabel
                    : data.extra && data.extra.noteText
                    ? data.extra.noteText
                    : "";

                  // 検索対象を全部くっつけて1本の文字列にする
                  const haystack = [
                    timeStr,                      // 時刻
                    data.businessKey || "",       // 営業日
                    data.deviceLabel || "未設定", // 端末
                    data.action || "",            // 操作
                    taskText || "",               // タスク / メモ
                  ]
                    .join(" ")
                    .toLowerCase();

                  return haystack.includes(keyword);
                });
              }

              renderLogs(docs, businessKey, deviceFilter);
              setLogStatus(`読み込み完了（${docs.length}件）`, true);
            },
            (error) => {
              console.error("checklistLogs 読み込みエラー", error);
              setLogStatus("ログの読み込みに失敗しました。", false);
              logTableBody.innerHTML =
                "<tr><td colspan='5'>ログの読み込みに失敗しました。</td></tr>";
            }
          );
      }

      // 日付ショートカットボタン
      const logTodayBtn = document.getElementById("logTodayBtn");
      const logYesterdayBtn = document.getElementById("logYesterdayBtn");
      const logLast3Btn = document.getElementById("logLast3Btn"); // まだ未実装

      function setDateInputToToday() {
        logDateInput.value = getCurrentBusinessKey();
      }

      function setDateInputToYesterday() {
        const today = getCurrentBusinessKey(); // YYYY-MM-DD
        const [y, m, d] = today.split("-").map(Number);
        const dt = new Date(y, m - 1, d);
        dt.setDate(dt.getDate() - 1);
        const yy = dt.getFullYear();
        const mm = String(dt.getMonth() + 1).padStart(2, "0");
        const dd = String(dt.getDate()).padStart(2, "0");
        logDateInput.value = `${yy}-${mm}-${dd}`;
      }

      logTodayBtn?.addEventListener("click", () => {
        setDateInputToToday();
        startLogsListener();
      });

      logYesterdayBtn?.addEventListener("click", () => {
        setDateInputToYesterday();
        startLogsListener();
      });

      logLast3Btn?.addEventListener("click", () => {
        alert("直近3日の集計はあとで実装しよう💡（今日は日付1日ずつ見てね）");
      });

      // フィルタボタン
      logFilterBtn.addEventListener("click", () => {
        startLogsListener();
      });

      // 端末ラベル・キーワード入力で Enter 押したら検索
      logDeviceInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") startLogsListener();
      });
      logKeywordInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") startLogsListener();
      });

      // 初期値：今日の営業日
      logDateInput.value = getCurrentBusinessKey();
      startLogsListener();

      // =============================
      // 6) グラフ分析（analytics）
      // =============================
      const analyticsDateInput = document.getElementById("analyticsDate");
      const analyticsRunBtn = document.getElementById("analyticsRunBtn");
      const analyticsTotalCount = document.getElementById("analyticsTotalCount");
      const analyticsSummaryNote = document.getElementById("analyticsSummaryNote");
      const analyticsTableBody = document.getElementById("analyticsTableBody");
      const analyticsEmptyMsg = document.getElementById("analyticsEmptyMsg");
      const analyticsStatus = document.getElementById("analyticsStatus");
      const deviceChartCanvas = document.getElementById("deviceChartCanvas");

      let deviceChart = null;

      function setAnalyticsStatus(msg, ok) {
        analyticsStatus.textContent = msg || "";
        analyticsStatus.className = "status " + (ok ? "ok" : "err");
      }

      async function runAnalytics() {
        let businessKey = businessKeyFromDateInput(analyticsDateInput.value);
        if (!businessKey) {
          businessKey = getCurrentBusinessKey();
          analyticsDateInput.value = businessKey;
        }

        setAnalyticsStatus("集計中…", true);
        analyticsTableBody.innerHTML =
          "<tr><td colspan='2'>集計中...</td></tr>";
        analyticsEmptyMsg.style.display = "none";
        analyticsTotalCount.textContent = "-";
        analyticsSummaryNote.textContent = "";

        try {
          const snapshot = await logsColRef
            .where("businessKey", "==", businessKey)
            .get();

          if (snapshot.empty) {
            analyticsTableBody.innerHTML =
              "<tr><td colspan='2'>この営業日のログがありません。</td></tr>";
            analyticsEmptyMsg.textContent = "営業日: " + businessKey;
            analyticsEmptyMsg.style.display = "block";
            analyticsTotalCount.textContent = "0";
            analyticsSummaryNote.textContent = "チェックログがありません。";
            if (deviceChart) {
              deviceChart.destroy();
              deviceChart = null;
            }
            setAnalyticsStatus("集計完了（0件）", true);
            return;
          }

          const perDevice = {};
          let total = 0;

          snapshot.forEach((doc) => {
            const data = doc.data() || {};
            const actionStr = (data.action || "").toLowerCase();

            // ★完了扱いの条件：
            // action に "check" or "on" が含まれているものを「チェックした」とみなす
            if (!actionStr) return;
            if (!actionStr.includes("check") && !actionStr.includes("on")) return;

            const device = data.deviceLabel || "未設定";
            perDevice[device] = (perDevice[device] || 0) + 1;
            total++;
          });

          // テーブル描画
          analyticsTableBody.innerHTML = "";
          const devices = Object.keys(perDevice);

          if (devices.length === 0) {
            analyticsTableBody.innerHTML =
              "<tr><td colspan='2'>「チェック完了」とみなせるログがありませんでした。</td></tr>";
            analyticsEmptyMsg.textContent =
              "営業日: " + businessKey + "（action の条件を調整すればカウントが増えるかもしれません）";
            analyticsEmptyMsg.style.display = "block";
          } else {
            devices
              .sort((a, b) => (perDevice[b] || 0) - (perDevice[a] || 0))
              .forEach((device) => {
                const tr = document.createElement("tr");
                const tdDevice = document.createElement("td");
                const tdCount = document.createElement("td");
                tdDevice.textContent = device;
                tdCount.textContent = perDevice[device];
                tr.appendChild(tdDevice);
                tr.appendChild(tdCount);
                analyticsTableBody.appendChild(tr);
              });
          }

          analyticsTotalCount.textContent = String(total);
          analyticsSummaryNote.textContent =
            devices.length > 0
              ? `端末数: ${devices.length} / 1端末あたり平均 ${(total / devices.length).toFixed(1)} 回`
              : "完了ログがないため平均は算出できません。";

          // グラフ描画
          if (deviceChart) {
            deviceChart.destroy();
            deviceChart = null;
          }
          if (devices.length > 0 && deviceChartCanvas) {
            const ctx = deviceChartCanvas.getContext("2d");
            deviceChart = new Chart(ctx, {
              type: "bar",
              data: {
                labels: devices,
                datasets: [
                  {
                    label: "チェック回数",
                    data: devices.map((d) => perDevice[d]),
                  },
                ],
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  y: {
                    beginAtZero: true,
                    ticks: {
                      precision: 0,
                    },
                  },
                },
                plugins: {
                  legend: {
                    display: false,
                  },
                },
              },
            });
          }

          setAnalyticsStatus(`集計完了（${total}回）`, true);
        } catch (e) {
          console.error("analytics 集計エラー", e);
          analyticsTableBody.innerHTML =
            "<tr><td colspan='2'>集計に失敗しました。</td></tr>";
          analyticsEmptyMsg.style.display = "none";
          if (deviceChart) {
            deviceChart.destroy();
            deviceChart = null;
          }
          analyticsTotalCount.textContent = "-";
          analyticsSummaryNote.textContent = "";
          setAnalyticsStatus("集計に失敗しました：" + (e.message || ""), false);
        }
      }

      analyticsRunBtn.addEventListener("click", () => {
        runAnalytics();
      });

      // activateView から呼べるように参照を渡しておく
      runAnalyticsRef = runAnalytics;
      analyticsDateInputRef = analyticsDateInput;
    });
  </script>
