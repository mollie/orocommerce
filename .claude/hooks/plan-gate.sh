#!/usr/bin/env bash
# Plan-section gate (.claude/plan-template.md, PRINCIPLES.md §2.3).
#
# PreToolUse  ExitPlanMode : deny leaving plan mode when the plan misses a mandatory section.
# PostToolUse Write|Edit   : when docs/specs/<feature>/plan.md was written, block with a reason that
#                            names the missing sections, so they are added before approval.
#
# stdin: the hook JSON. The plan text is tool_input.plan, or the file at tool_input.planFilePath.
# The JSON is read with python3, or php as a fallback. If neither exists, or the input can't be
# judged, the hook allows (fail-open); the reviews in PRINCIPLES.md §2.6 still apply.

input="$(cat)"

extract() {
    local code='
import json, sys
try:
    j = json.loads(sys.stdin.read())
except Exception:
    sys.exit(0)
t = j.get("tool_input") or {}
for v in (j.get("hook_event_name"), j.get("tool_name"), t.get("plan"), t.get("planFilePath"), t.get("file_path")):
    sys.stdout.write((v if isinstance(v, str) else "") + "\0")
'
    local php_code='
$j = json_decode(stream_get_contents(STDIN), true);
if (!is_array($j)) { exit(0); }
$t = isset($j["tool_input"]) && is_array($j["tool_input"]) ? $j["tool_input"] : array();
foreach (array(@$j["hook_event_name"], @$j["tool_name"], @$t["plan"], @$t["planFilePath"], @$t["file_path"]) as $v) {
    echo (is_string($v) ? $v : ""), "\0";
}
'
    if command -v python3 >/dev/null 2>&1; then
        printf '%s' "$input" | python3 -c "$code"
    elif command -v php >/dev/null 2>&1; then
        printf '%s' "$input" | php -r "$php_code"
    else
        echo "plan-gate: neither python3 nor php found, plan sections not checked" >&2
    fi
}

fields=()
while IFS= read -r -d '' f; do fields+=("$f"); done < <(extract)
[ "${#fields[@]}" -eq 5 ] || exit 0
event="${fields[0]}"; tool="${fields[1]}"; plan="${fields[2]}"; plan_file="${fields[3]}"; file_path="${fields[4]}"

# Prints the mandatory sections the plan text lacks, comma-separated.
missing_sections() {
    local text="$1" missing=()
    grep -Eiq '^#{1,6}.*architecture[[:space:]]+impact' <<<"$text" || missing+=('Architecture impact')
    grep -Eiq '^#{1,6}.*risks[[:space:]]*(&|and)[[:space:]]*emphasis' <<<"$text" || missing+=('Risks & emphasis')
    grep -Eiq '^#{1,6}.*verification' <<<"$text" || missing+=('Verification')
    local IFS=','; printf '%s' "${missing[*]}" | sed 's/,/, /g'
}

reason_for() {
    printf 'Plan blocked: missing mandatory section heading(s): %s. Every plan (light or full) needs Architecture impact, Risks & emphasis and Verification per .claude/plan-template.md and PRINCIPLES.md section 2.3. Add them to the plan' "$1"
}

if [ "$event" = "PreToolUse" ] && [ "$tool" = "ExitPlanMode" ]; then
    if [ -z "$plan" ] && [ -n "$plan_file" ] && [ -f "$plan_file" ]; then
        plan="$(cat "$plan_file")"
    fi
    [ "${#plan}" -ge 80 ] || exit 0
    missing="$(missing_sections "$plan")"
    [ -n "$missing" ] || exit 0
    printf '{"hookSpecificOutput": {"hookEventName": "PreToolUse", "permissionDecision": "deny", "permissionDecisionReason": "%s and exit plan mode again."}}\n' "$(reason_for "$missing")"
    exit 0
fi

if [ "$event" = "PostToolUse" ]; then
    case "${file_path//\\//}" in
        */docs/specs/*/plan.md) ;;
        *) exit 0 ;;
    esac
    [ -f "$file_path" ] || exit 0
    missing="$(missing_sections "$(cat "$file_path")")"
    [ -n "$missing" ] || exit 0
    rel="${file_path//\\//}"; rel="${rel#"${CLAUDE_PROJECT_DIR//\\//}"/}"; rel="${rel//\"/}"
    printf '{"decision": "block", "reason": "%s file %s before asking the developer to approve it."}\n' "$(reason_for "$missing")" "$rel"
    exit 0
fi

exit 0
