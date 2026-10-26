import { Calendar } from "fullcalendar";
import dayGridPlugin from "fullcalendar/daygrid";
import interactionPlugin from "fullcalendar/interaction";
import esLocale from "fullcalendar/locales/es";
import jaLocale from "fullcalendar/locales/ja";
import themePlugin from "fullcalendar/themes/classic";

import "fullcalendar/skeleton.css";
import "fullcalendar/themes/classic/palette.css";
import "fullcalendar/themes/classic/theme.css";

// Read-only calendar: users only see the marked days (e.g. working days),
// they cannot create or edit events.
export function calendarInit() {
  const calendarEl = document.querySelector("#calendar");

  if (!calendarEl) return;

  const isRtl = document.documentElement.dir === "rtl";
  // Page language (es, en or ja) and the texts the server translated for this calendar
  const lang = (document.documentElement.lang || "es").slice(0, 2);
  const texts = { labels: "Etiquetas", di: "Instrucción de entrega", more: "más", downloaded: "Descargada", changed: "Cambió después de la descarga", ...JSON.parse(calendarEl.dataset.i18n || "{}") };
  let isMobile = window.innerWidth < 640;

  // Background colors for marked days, by event "type"
  const BG_EVENT_CLASSES = {
    success:
      "bg-success-100! opacity-100! dark:bg-success-500/20! text-success-700! dark:text-success-400!",
    primary:
      "bg-brand-100! opacity-100! dark:bg-brand-500/20! text-brand-700! dark:text-brand-400!",
    danger:
      "bg-error-100! opacity-100! dark:bg-error-500/20! text-error-700! dark:text-error-400!",
    warning:
      "bg-warning-100! opacity-100! dark:bg-warning-500/20! text-warning-700! dark:text-warning-400!",
    weekend:
      "bg-gray-100! opacity-100! dark:bg-white/5! text-gray-500! dark:text-gray-400!",
  };

  const EVENT_COLOR_CLASSES = {
    success: {
      bg: "border border-success-100 bg-success-50 dark:border-success-500/20 dark:bg-success-500/15",
      dot: "bg-success-500",
    },
    danger: {
      bg: "border border-error-100 bg-error-50 dark:border-error-500/20 dark:bg-error-500/15",
      dot: "bg-error-500",
    },
    primary: {
      bg: "border border-brand-100 bg-brand-50 dark:border-brand-500/20 dark:bg-brand-500/15",
      dot: "bg-brand-500",
    },
    warning: {
      bg: "border border-warning-100 bg-warning-50 dark:border-warning-500/20 dark:bg-warning-500/15",
      dot: "bg-warning-500",
    },
    // Supplier documents (labels and DI)
    labels: {
      bg: "border border-success-200 bg-success-50 hover:bg-success-100 dark:border-success-500/30 dark:bg-success-500/15 dark:hover:bg-success-500/25",
      dot: "bg-success-500",
    },
    di: {
      bg: "border border-blue-light-200 bg-blue-light-50 hover:bg-blue-light-100 dark:border-blue-light-500/30 dark:bg-blue-light-500/15 dark:hover:bg-blue-light-500/25",
      dot: "bg-blue-light-500",
    },
  };

  const PAST_DOCUMENT_CLASSES = {
    bg: "border border-gray-200 bg-gray-100 opacity-80 hover:opacity-100 dark:border-gray-700 dark:bg-white/5",
    dot: "bg-gray-400 dark:bg-gray-500",
  };

  // Supplier documents per day ("2026-10-05" -> { labels, di }), filled by
  // the documents event source and read when a day is clicked.
  const documentsUrl = calendarEl.dataset.documentsUrl;
  let documentsByDay = {};

  function documentEvents(days) {
    const events = [];
    for (const [date, info] of Object.entries(days)) {
      if (info.labels > 0) {
        events.push({
          id: `labels-${date}`,
          title: texts.labels,
          start: date,
          allDay: true,
          extendedProps: { type: "labels", document: true, download: info.downloads?.labels ?? null },
        });
      }
      if (info.di > 0) {
        events.push({
          id: `di-${date}`,
          title: texts.di,
          start: date,
          allDay: true,
          extendedProps: { type: "di", document: true, download: info.downloads?.di ?? null },
        });
      }
    }
    return events;
  }

  // Event source: asks the server which days have labels or DI in the visible range
  async function fetchDocuments(info) {
    if (!documentsUrl) return [];
    const params = new URLSearchParams({
      start: info.startStr.slice(0, 10),
      end: info.endStr.slice(0, 10),
    });
    try {
      const response = await fetch(`${documentsUrl}?${params}`, {
        headers: { Accept: "application/json" },
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const days = await response.json();
      documentsByDay = { ...documentsByDay, ...days };
      calendarEl.dispatchEvent(new CustomEvent("documents-status", { detail: { ok: true }, bubbles: true }));
      return documentEvents(days);
    } catch (error) {
      console.error("Could not load supplier documents", error);
      calendarEl.dispatchEvent(new CustomEvent("documents-status", { detail: { ok: false }, bubbles: true }));
      return [];
    }
  }

  // After a download: check mark; if Infor changed the data afterwards, a warning instead
  function downloadIcon(download) {
    if (!download) return "";
    if (download.changed) {
      return `<span class="ms-1 shrink-0 text-warning-500" title="${texts.changed}" aria-label="${texts.changed}"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01" /><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" /></svg></span>`;
    }
    return `<span class="ms-1 shrink-0 text-success-600 dark:text-success-400" title="${texts.downloaded}" aria-label="${texts.downloaded}"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg></span>`;
  }

  // What each day is in the work calendar ("2026-10-12" -> "holiday" | "half_shift" | "rest" | "other");
  // working days are not in it
  const dayKinds = {};
  function markDays(start, end, kind) {
    const day = new Date(`${start}T12:00:00`);
    const last = end ? new Date(`${end}T12:00:00`) : new Date(day.getTime() + 86400000); // end is exclusive
    while (day < last) {
      const key = `${day.getFullYear()}-${String(day.getMonth() + 1).padStart(2, "0")}-${String(day.getDate()).padStart(2, "0")}`;
      dayKinds[key] = kind;
      day.setDate(day.getDate() + 1);
    }
  }

  // Opens the day modal (calender-area.blade.php listens for this event)
  function openDay(dateStr) {
    const date = dateStr.slice(0, 10);
    if (visibleFrom && date < visibleFrom) return;
    const info = documentsByDay[date] || { labels: 0, di: 0 };
    const past = Boolean(availableFrom) && date < availableFrom;
    const workday = dayKinds[date] || null;
    window.dispatchEvent(new CustomEvent("calendar-day", { detail: { date, past, workday, ...info } }));
  }

  // Events come from the server as JSON in data-events:
  // [{ "start": "2026-09-01", "end": "2026-09-05", "title": "Laboral", "type": "success" }]
  // By default they render as a label (display: "block"); pass
  // "display": "background" to shade the whole day instead (e.g. weekends).
  function parseEvents() {
    let raw = [];
    try {
      raw = JSON.parse(calendarEl.dataset.events || "[]");
    } catch (e) {
      console.error("Invalid calendar events JSON", e);
    }

    for (const event of raw) {
      if (event.kind) markDays(event.start, event.end, event.kind);
    }

    return raw.map((event, index) => {
      const type = String(event.type || "success").toLowerCase();
      const display = event.display || "block";
      return {
        id: String(event.id ?? index),
        title: event.title || "",
        start: event.start,
        end: event.end,
        allDay: true,
        display,
        className:
          display === "background"
            ? BG_EVENT_CLASSES[type] || BG_EVENT_CLASSES.success
            : "",
        extendedProps: { type },
      };
    });
  }

  // Month title: "octubre 2026" (instead of "octubre de 2026"), "October 2026", "2026年10月"
  const monthNameFormatter = new Intl.DateTimeFormat(lang, { month: "long" });
  const monthYearFormatter = new Intl.DateTimeFormat(lang, { year: "numeric", month: "long" });
  const formatMonthYear = ({ date }) => {
    const first = new Date(date.year, date.month, 1);
    return lang === "es" ? `${monthNameFormatter.format(first)} ${date.year}` : monthYearFormatter.format(first);
  };

  const bookmarkIcon = `<span><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 sm:size-5.5 text-brand-500"><path d="M19 3v17a1 1 0 01-1.496.868l-4.512-2.578a2 2 0 00-1.984 0l-4.512 2.578A1 1 0 015 20V3z" /></svg></span>`;

  // Today: brand ring + bold number, no background so marked-day colors stay visible
  const todayCellClass =
    "z-1! ring-2! ring-inset! ring-brand-500! dark:ring-brand-400!";
  const todayNumberClass = "font-bold! text-brand-600! dark:text-brand-400!";

  const popoverHeaderClass =
    "relative! border-b! border-gray-200! bg-gray-50/70! px-4! py-3! text-start! dark:border-gray-800! dark:bg-gray-800/50!";

  function buildViews() {
    return {
      dayGridMonth: {
        titleFormat: formatMonthYear,
        tableClass: "overflow-hidden",
        dayMaxEvents: isMobile ? 1 : 2,
        dayHeaderAlign: (data) => (data.inPopover ? "start" : "center"),
        dayHeaderClass: (data) =>
          data.inPopover
            ? popoverHeaderClass
            : "border-x-0! border-t border-gray-200! bg-gray-50 dark:border-gray-800! dark:bg-gray-900",
        dayHeaderInnerClass: (data) =>
          data.inPopover
            ? "text-sm! font-semibold! text-gray-800! dark:text-white/90!"
            : "px-1! py-2! sm:px-3! sm:py-3! md:px-5! md:py-4! text-xs! sm:text-sm! font-medium! text-gray-400 uppercase",
        dayCellClass: (data) => {
          if (data.inPopover) return "bg-transparent! p-3!";
          if (isPast(data.date)) {
            const viewable = !visibleFromDate || data.date >= visibleFromDate;
            return `calendar-day-past p-1! sm:p-2! ${viewable ? "cursor-pointer" : ""}`;
          }
          return `cursor-pointer bg-transparent! p-1! sm:p-2! ${data.isToday ? todayCellClass : ""}`;
        },
        dayCellInnerClass: (data) => {
          if (data.inPopover)
            return "flex custom-scrollbar max-h-60 flex-col gap-1.5 overflow-y-auto";
          return "";
        },
        dayCellTopInnerClass: (data) => {
          if (isPast(data.date)) return "text-gray-400! line-through decoration-2 dark:text-gray-600!";
          return data.isToday ? todayNumberClass : "text-gray-700 dark:text-gray-300";
        },
        moreLinkClass:
          "border-0! bg-transparent! p-0! hover:bg-transparent! focus:outline-none",
        moreLinkContent: (args) => {
          if (isMobile) return { html: bookmarkIcon };
          return {
            html: `<span class="fc-more-link-badge inline-flex items-center rounded-sm bg-brand-50 px-1 py-0.5 sm:px-1.5 text-[10px] sm:text-xs font-medium text-brand-600 transition-colors hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">+${args.num} ${texts.more}</span>`,
          };
        },
      },

    };
  }

  // The month fills the remaining viewport height
  function computeHeight() {
    const card = calendarEl.closest("[data-calendar-card]") || calendarEl;
    const cardTop = card.getBoundingClientRect().top + window.scrollY;
    const extras = card.offsetHeight - calendarEl.offsetHeight; // warning banner, borders
    const bottomGap = window.innerWidth >= 768 ? 24 : 16; // layout padding
    return Math.max(480, window.innerHeight - cardTop - extras - bottomGap);
  }

  // Documents can be opened from the Monday of the current week on (CalendarController::availableFrom)
  const availableFrom = calendarEl.dataset.availableFrom || null;
  const availableFromDate = availableFrom ? new Date(`${availableFrom}T00:00:00`) : null;
  // Days before the current week are past: crossed out, they show what they had but cannot be downloaded
  const isPast = (date) => Boolean(availableFromDate) && date < availableFromDate;
  // The calendar goes back one month (CalendarController::visibleFrom)
  const visibleFrom = calendarEl.dataset.visibleFrom || null;
  const visibleFromDate = visibleFrom ? new Date(`${visibleFrom}T00:00:00`) : null;
  const toolbar = calendarEl.closest("[data-calendar-card]")?.querySelector("[data-calendar-toolbar]");
  const titleEl = toolbar?.querySelector("[data-calendar-title]");
  const navButtons = Object.fromEntries(
    [...(toolbar?.querySelectorAll("[data-calendar-nav]") ?? [])].map((button) => [button.dataset.calendarNav, button]),
  );

  const calendar = new Calendar(calendarEl, {
    plugins: [themePlugin, dayGridPlugin, interactionPlugin],
    locales: [esLocale, jaLocale],
    locale: lang,
    initialView: "dayGridMonth",
    direction: isRtl ? "rtl" : "ltr",
    height: computeHeight(),

    // Read-only
    editable: false,
    selectable: false,

    // The toolbar is drawn in calender-area.blade.php (vendor | month | navigation)
    headerToolbar: false,
    datesSet(info) {
      if (titleEl) titleEl.textContent = info.view.title;
      const { currentStart, currentEnd } = info.view;
      const today = new Date();
      // One month back at most
      if (navButtons.prev) {
        navButtons.prev.disabled = Boolean(visibleFromDate) && currentStart <= visibleFromDate;
      }
      if (navButtons.today) navButtons.today.disabled = today >= currentStart && today < currentEnd;
    },

    views: buildViews(),

    borderless: true,
    viewClass:
      "border-t! border-b-0! border-x-0! border-gray-200! bg-transparent! dark:border-gray-800! dark:bg-transparent!",
    dayHeaderDividerClass:
      "border-b! border-t-0! border-x-0! border-gray-200! p-0! bg-transparent! dark:border-gray-800!",
    tableHeaderSticky: true,
    tableClass: "bg-transparent!",
    eventClass: "focus:shadow-none cursor-pointer",
    rowEventClass:
      "bg-transparent! border-0! px-1! py-0.5! shadow-none! hover:shadow-none! focus:outline-none",
    rowEventInnerClass: "w-full p-0! border-0! bg-transparent!",
    backgroundEventTitleClass:
      "hidden! sm:block! text-[10px]! sm:text-xs! font-medium! not-italic! opacity-100!",
    popoverFormat: { month: "short", day: "numeric", year: "numeric" },
    popoverClass:
      "z-99999! w-72 max-w-[calc(100vw-32px)] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900",
    popoverCloseClass:
      "absolute end-3 top-2.5 flex size-7 cursor-pointer items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white",
    popoverCloseContent: {
      html: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M18 6L6 18M6 6l12 12" /></svg>`,
    },

    eventSources: [{ events: parseEvents() }, { events: fetchDocuments }],
    dateClick(info) {
      openDay(info.dateStr);
    },
    eventClick(info) {
      info.jsEvent.preventDefault();
      openDay(info.event.startStr);
    },
    eventContent(eventInfo) {
      const type = eventInfo.event.extendedProps?.type || "primary";
      // Documents of past days: muted, they can be looked at but not downloaded
      const pastDocument = eventInfo.event.extendedProps?.document && isPast(eventInfo.event.start);
      const colors = pastDocument ? PAST_DOCUMENT_CLASSES : EVENT_COLOR_CLASSES[type] || EVENT_COLOR_CLASSES.primary;

      // Phones: no room for text, each label becomes a solid bar in its color
      if (isMobile) {
        return {
          html: `<div class="mt-1 h-1.5 w-full rounded-full ${colors.dot}"></div>`,
        };
      }

      return {
        html: `
          <div class="flex max-w-full items-center rounded-md py-1 ps-1.5 pe-2 sm:rounded-lg sm:py-1.5 sm:ps-2.5 sm:pe-3 ${colors.bg}">
            <div class="ms-0 me-1 h-2.5 w-1 shrink-0 rounded-full sm:me-2 sm:h-3.5 ${colors.dot}"></div>
            <div class="fc-event-title min-w-0 flex-1 truncate p-0 text-[11px] font-medium text-gray-700 sm:text-xs dark:text-white">${eventInfo.event.title || ""}</div>
            ${downloadIcon(eventInfo.event.extendedProps?.download)}
          </div>
        `,
      };
    },
  });

  navButtons.prev?.addEventListener("click", () => calendar.prev());
  navButtons.next?.addEventListener("click", () => calendar.next());
  navButtons.today?.addEventListener("click", () => calendar.today());

  // A finished download (calender-area.blade.php) shows its check mark right away
  window.addEventListener("calendar-document-downloaded", () => calendar.refetchEvents());

  calendar.render();
  // Re-measure once the toolbar is laid out
  requestAnimationFrame(() => calendar.setOption("height", computeHeight()));

  let resizeFrame = null;
  window.addEventListener("resize", () => {
    cancelAnimationFrame(resizeFrame);
    resizeFrame = requestAnimationFrame(() => {
      const mobile = window.innerWidth < 640;
      if (isMobile !== mobile) {
        isMobile = mobile;
        calendar.setOption("views", buildViews());
      }
      calendar.setOption("height", computeHeight());
    });
  });
}
