/*! Minimalist Web Notepad | https://github.com/pereorga/minimalist-web-notepad */

// polyfill
AbortSignal.timeout ??= function timeout(ms) {
  const ctrl = new AbortController()
  setTimeout(() => ctrl.abort(), ms)
  return ctrl.signal
}

async function uploadContent(data) {
    const temp = textarea.value;
    try {
        const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: data,
            signal: AbortSignal.timeout(6000)
        })
        if (!resp.ok) {
            throw new Error(`${resp.status} ${resp.statusText}`)
        } else {
            const titleArr = document.title.split('|');
            if (titleArr.length > 1) {
                document.title = titleArr[0];
                document.body.classList.remove('warning');
            }
            onServer = temp;
            document.body.classList.add('flash')
        }
    } catch (error) {
        const titleArr = document.title.split('|');
        if (titleArr.length = 1) {
            document.title = titleArr[0] + '| !!NETWORK ERROR!!';
            document.body.classList.add('warning');
        }
        console.error('Error:', error.message);
    }
}

const textarea = document.getElementById('content');
const printable = document.getElementById('printable');
const debounce = (callback, wait) => {
    let timeoutId = null;
    return (...args) => {
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => {
            callback(...args);
        }, wait);
    };
}
const updateTrigger = debounce(async () => {
    if (onServer === textarea.value) return;
    document.body.classList.remove('flash')
    await uploadContent(new URLSearchParams({
        'text': textarea.value
    }))
}, 2000);
const toggleView = () => {
    if (!window.markdownit) return;
    if (!md) md = window.markdownit();
    const isPrintView = textarea.classList.toggle('hide');
    if (isPrintView) printable.innerHTML = md.render(textarea.value);
    printable.classList.toggle('hide');
}
var onServer = textarea.value;
var md = null;

if (window.location.hash == "#md") toggleView();

// bind textarea update event
textarea.addEventListener('input', updateTrigger);

// bind Esc keypress event
document.addEventListener('keyup', function(e) {
    if (e.key === 'Escape') toggleView();
})

textarea.focus();
