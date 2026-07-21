use std::io::{self, BufRead, Write};
use whatlang::detect;

// Reads one hex-encoded UTF-8 text per line, prints "lang<TAB>script<TAB>confidence".
fn main() {
    let stdin = io::stdin();
    let stdout = io::stdout();
    let mut out = stdout.lock();

    for line in stdin.lock().lines() {
        let line = line.unwrap();
        let line = line.trim();

        if line.is_empty() {
            continue;
        }

        let bytes: Vec<u8> = (0..line.len())
            .step_by(2)
            .map(|i| u8::from_str_radix(&line[i..i + 2], 16).unwrap())
            .collect();
        let text = String::from_utf8(bytes).unwrap();

        match detect(&text) {
            Some(info) => writeln!(
                out,
                "{}\t{}\t{:.12}",
                info.lang().code(),
                info.script().name(),
                info.confidence()
            )
            .unwrap(),
            None => writeln!(out, "-\t-\t0.000000000000").unwrap(),
        }
    }
}
