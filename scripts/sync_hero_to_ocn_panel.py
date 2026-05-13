#!/usr/bin/env python3
"""Replace rounded-2xl hero blocks with ocn-panel + ocn-panel__head (Kelola User / ERP style)."""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "resources" / "js" / "Pages"

HERO_OPEN = '<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">'

# Projects/Index: outer row first, label inside left column
RE_PROJECTS_INDEX = re.compile(
    r'^<div class="flex flex-wrap items-(?:center|start) justify-between gap-3">\s*'
    r'<div>\s*'
    r'<p class="text-xs font-bold uppercase[^>]*>(?P<label>[\s\S]*?)</p>\s*'
    r'<h1[^>]*>(?P<title>[\s\S]*?)</h1>\s*'
    r'(?P<desc><p class="mt-2 text-sm text-base-content/70"[\s\S]*?</p>\s*)?'
    r'</div>\s*'
    r'(?P<right>[\s\S]*?)\s*</div>\s*$',
    re.DOTALL,
)

RE_LABEL = re.compile(
    r'^\s*<p class="text-xs font-bold uppercase[^>]*>([\s\S]*?)</p>\s*',
    re.DOTALL,
)

RE_ROW_PREFIX = re.compile(
    r'^<div class="(?:mt-2 )?flex(?: flex-wrap)?(?: items-(?:center|start))? justify-between gap-3">\s*',
    re.DOTALL,
)


def find_hero_block(text: str, start: int = 0):
    j = text.find(HERO_OPEN, start)
    if j == -1:
        return None
    inner_start = j + len(HERO_OPEN)
    depth = 1
    i = inner_start
    while depth > 0 and i < len(text):
        sub = text[i:]
        no = sub.find("<div")
        nc = sub.find("</div>")
        if nc == -1:
            return None
        if no != -1 and no < nc:
            depth += 1
            i += no + 4
        else:
            if depth == 1:
                inner_end = i + nc
                outer_end = i + nc + len("</div>")
                return j, inner_start, inner_end, outer_end
            depth -= 1
            i += nc + len("</div>")
    return None


def consume_balanced_div(s: str):
    """If s starts with <div...>, return (inner_html, remainder_after_closing_div)."""
    t = s.lstrip()
    m = re.match(r"<div[^>]*>", t)
    if not m:
        return None
    inner_start = m.end()
    depth = 1
    i = inner_start
    while depth > 0 and i < len(t):
        sub = t[i:]
        no = sub.find("<div")
        nc = sub.find("</div>")
        if nc == -1:
            return None
        if no != -1 and no < nc:
            depth += 1
            i += no + 4
        else:
            if depth == 1:
                inside = t[inner_start : i + nc]
                after = t[i + nc + len("</div>") :].lstrip()
                return inside.strip(), after
            depth -= 1
            i += nc + len("</div>")
    return None


def ensure_arrow_import(content: str) -> str:
    if "ArrowLeftIcon" in content:
        return content
    m = re.search(
        r"import\s*\{([^}]*)\}\s*from\s*['\"]@heroicons/vue/24/outline['\"]",
        content,
        re.DOTALL,
    )
    if m:
        inner = m.group(1).strip()
        new_inner = f"ArrowLeftIcon,\n  {inner}" if inner else "ArrowLeftIcon"
        return content[: m.start(1)] + new_inner + content[m.end(1) :]
    ins = list(re.finditer(r"from '@inertiajs/vue3';", content))
    if ins:
        pos = ins[-1].end()
        return (
            content[:pos]
            + "\nimport { ArrowLeftIcon } from '@heroicons/vue/24/outline';"
            + content[pos:]
        )
    return content


def _normalize_link_classes(open_tag: str) -> str:
    if "btn-ghost" not in open_tag:
        return open_tag
    if "shrink-0" in open_tag and "gap-1.5" in open_tag:
        return open_tag
    if 'class="' in open_tag:
        return open_tag.replace(
            'class="btn btn-ghost btn-sm"',
            'class="btn btn-ghost btn-sm shrink-0 gap-1.5"',
            1,
        )
    return open_tag


def inject_back_icon(fragment: str) -> str:
    """ArrowLeftIcon on first Back ghost Link or button."""
    if "ArrowLeftIcon" in fragment:
        return fragment

    def repl_class_first(m):
        open_tag = _normalize_link_classes(m.group(1))
        mid = m.group(2)
        body = m.group(3).strip()
        close = m.group(4)
        if body == "Back":
            body = '<ArrowLeftIcon class="h-4 w-4" />\n            Back'
        return f"{open_tag}{mid}{body}{close}"

    s = re.sub(
        r'(<Link\s+class="btn btn-ghost btn-sm(?:\s+[^"]*)?"\s*)'
        r'(:href="[^"]*"\s*[^>]*>)'
        r"(\s*)(Back\s*)(</Link>)",
        repl_class_first,
        fragment,
        count=1,
    )
    if "ArrowLeftIcon" in s:
        return s

    def repl_href_first(m):
        pre = m.group(1)
        href = m.group(2)
        open_tag = _normalize_link_classes(m.group(3))
        ws = m.group(4)
        body = m.group(5).strip()
        close = m.group(6)
        if body == "Back":
            body = '<ArrowLeftIcon class="h-4 w-4" />\n                            Back'
        return f"{pre}{href}{open_tag}{ws}{body}{close}"

    s = re.sub(
        r"(<Link\s+)"
        r'(:href="[^"]*"\s+)'
        r'(class="btn btn-ghost btn-sm(?:\s+[^"]*)?"\s*[^>]*>)'
        r"(\s*)(Back\s*)(</Link>)",
        repl_href_first,
        s,
        count=1,
    )
    if "ArrowLeftIcon" in s:
        return s

    def repl_btn(m):
        open_tag = m.group(1)
        if "gap-1.5" in open_tag:
            return m.group(0)
        if 'class="' in open_tag:
            open_tag = re.sub(
                r'class="([^"]*)"',
                lambda m2: f'class="{m2.group(1)} shrink-0 gap-1.5"',
                open_tag,
                count=1,
            )
        return f'{open_tag}{m.group(2)}<ArrowLeftIcon class="h-4 w-4" />\n            Back{m.group(4)}'

    s = re.sub(
        r'(<button[^>]*class="[^"]*btn btn-ghost btn-sm[^"]*"[^>]*>)(\s*)(Back\s*)(</button>)',
        repl_btn,
        s,
        count=1,
    )
    return s


def _trail_inner(trail: str | None) -> str:
    if not trail or not trail.strip():
        return ""
    dm = re.search(r">([\s\S]*)</p>", trail.strip(), re.DOTALL)
    return dm.group(1).strip() if dm else ""


def _normalize_subhtml(html: str) -> str:
    if not html:
        return ""
    out = html
    out = re.sub(
        r'class="mt-2 text-sm text-base-content/70"',
        'class="ocn-panel__desc mt-1"',
        out,
    )
    out = re.sub(
        r'class="mt-1 text-sm text-base-content/70"',
        'class="ocn-panel__desc mt-1"',
        out,
    )
    out = re.sub(
        r'class="mt-1 text-xs text-base-content/60"',
        'class="text-xs text-base-content/60 mt-1"',
        out,
    )
    out = re.sub(r'class="text-base-content/60"', 'class="text-sm text-base-content/60 mt-1"', out)
    return out


def build_panel(label_html: str, title_html: str, sub_html: str, trail_inner: str, right_html: str) -> str:
    right_raw = right_html.strip()
    right = inject_back_icon(right_raw) if right_raw else ""
    parts = []
    if sub_html.strip():
        frag = _normalize_subhtml(sub_html.strip())
        if frag:
            parts.append(f"\n              {frag}")
    if trail_inner.strip():
        parts.append(f'\n              <p class="ocn-panel__desc mt-1">{trail_inner}</p>')
    body_extra = "".join(parts)
    if right_raw:
        return f"""<div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">{label_html}</p>
              <h1 class="ocn-panel__title mt-1">{title_html}</h1>{body_extra}
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              {right}
            </div>
          </div>
        </div>
      </div>"""
    return f"""<div class="ocn-panel">
        <div class="ocn-panel__head">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">{label_html}</p>
            <h1 class="ocn-panel__title mt-1">{title_html}</h1>{body_extra}
          </div>
        </div>
      </div>"""


def parse_leftcol_title_rest(leftcol: str):
    """leftcol: content inside first column div. Returns (label_html|None, title_html, rest_after_h1)."""
    lc = leftcol.strip()
    inner_label = None
    lm = RE_LABEL.match(lc)
    if lm:
        inner_label = lm.group(1).strip()
        lc = lc[lm.end() :].lstrip()
    hm = re.match(r"<h1[^>]*>(?P<title>[\s\S]*?)</h1>\s*", lc, re.DOTALL)
    if not hm:
        return None
    title = hm.group("title").strip()
    rest = lc[hm.end() :].strip()
    return inner_label, title, rest


def transform_hero_inner(inner: str) -> str | None:
    s = inner.strip()

    m = RE_PROJECTS_INDEX.match(s)
    if m:
        label = m.group("label").strip()
        title = m.group("title").strip()
        desc_inner = ""
        if m.group("desc"):
            desc_inner = _trail_inner(m.group("desc"))
        sub = ""
        if desc_inner:
            sub = f'<p class="ocn-panel__desc mt-1">{desc_inner}</p>'
        return build_panel(label, title, sub, "", m.group("right"))

    pre_label = None
    lm = RE_LABEL.match(s)
    if lm:
        pre_label = lm.group(1).strip()
        s = s[lm.end() :].lstrip()

    rm = RE_ROW_PREFIX.match(s)
    if not rm:
        return None
    row_rest = s[rm.end() :]
    parsed = consume_balanced_div(row_rest)
    if not parsed:
        return None
    left_inner, after_left = parsed
    # after_left = RIGHT markup + closing row </div> + optional trail (<p>…)
    last_close = after_left.rfind("</div>")
    if last_close == -1:
        return None
    right = after_left[:last_close].strip()
    row_tail = after_left[last_close + len("</div>") :].lstrip()
    trail_inner = _trail_inner(row_tail) if row_tail else ""

    plr = parse_leftcol_title_rest(left_inner)
    if not plr:
        return None
    inner_label, title, sub_after_h1 = plr
    label = pre_label if pre_label is not None else (inner_label or "")
    if pre_label is not None and inner_label is not None:
        # Unlikely: two labels — keep pre as workspace, prepend inner to sub
        sub_after_h1 = (
            f'<p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">{inner_label}</p>\n'
            + sub_after_h1
        )
    return build_panel(label, title, sub_after_h1, trail_inner, right)


def process_file(path: Path) -> bool:
    raw = path.read_text(encoding="utf-8")
    if HERO_OPEN not in raw:
        return False
    new = raw
    changed = False
    pos = 0
    while True:
        blk = find_hero_block(new, pos)
        if not blk:
            break
        j, inner_start, inner_end, outer_end = blk
        inner = new[inner_start:inner_end]
        replacement = transform_hero_inner(inner)
        if replacement is None:
            pos = outer_end
            continue
        new = new[:j] + replacement + new[outer_end:]
        changed = True
        pos = j + len(replacement)
    if not changed:
        return False
    new = ensure_arrow_import(new)
    path.write_text(new, encoding="utf-8")
    return True


def main():
    targets = list(ROOT.rglob("*.vue"))
    done = []
    skipped = []
    for p in sorted(targets):
        try:
            raw = p.read_text(encoding="utf-8")
            if process_file(p):
                done.append(str(p.relative_to(ROOT.parents[1])))
            elif HERO_OPEN in raw:
                skipped.append(str(p.relative_to(ROOT.parents[1])))
        except Exception as e:
            print(f"ERR {p}: {e}", file=sys.stderr)
    for d in done:
        print("OK", d)
    for d in skipped:
        print("SKIP", d)


if __name__ == "__main__":
    main()
