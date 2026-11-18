import { Eye, ImagePlus, RefreshCw, Trash2 } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import type { ChangeEvent, DragEvent, PointerEvent } from "react";
import AvatarPreview from "@/components/avatar-preview";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { useInitials } from "@/hooks/use-initials";

const VIEWPORT_SIZE = 256;
const OUTPUT_SIZE = 512;
const MAX_FILE_SIZE = 2 * 1024 * 1024;
const ACCEPTED_TYPES = ["image/jpeg", "image/png", "image/webp"];

const ERRORS = {
    type: "Only JPG, PNG, or WebP images are allowed.",
    size: "The image must be 2 MB or smaller.",
    read: "The image could not be read.",
    crop: "The image could not be cropped.",
};

type Props = {
    currentUrl: string | null;
    name: string;
    onChange: (file: File | null) => void;
    onRemove: () => void;
};

export default function AvatarEditor({
    currentUrl,
    name,
    onChange,
    onRemove,
}: Props) {
    const getInitials = useInitials();

    const fileInput = useRef<HTMLInputElement>(null);
    const dragOrigin = useRef({
        active: false,
        pointerX: 0,
        pointerY: 0,
        offsetX: 0,
        offsetY: 0,
    });

    const [isDragging, setIsDragging] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [removed, setRemoved] = useState(false);
    const [workingUrl, setWorkingUrl] = useState<string | null>(null);
    const [sourceImage, setSourceImage] = useState<HTMLImageElement | null>(
        null,
    );
    const [dialogOpen, setDialogOpen] = useState(false);
    const [zoom, setZoom] = useState(1);
    const [offset, setOffset] = useState({ x: 0, y: 0 });
    const [previewOpen, setPreviewOpen] = useState(false);

    // Revoke object URLs when they are replaced or on unmount.
    useEffect(
        () => () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
        },
        [previewUrl],
    );

    useEffect(
        () => () => {
            if (workingUrl) URL.revokeObjectURL(workingUrl);
        },
        [workingUrl],
    );

    const displayedUrl = removed ? null : (previewUrl ?? currentUrl);

    const baseScale = sourceImage
        ? Math.max(
              VIEWPORT_SIZE / sourceImage.naturalWidth,
              VIEWPORT_SIZE / sourceImage.naturalHeight,
          )
        : 1;

    const imageStyle = sourceImage
        ? {
              width: `${sourceImage.naturalWidth * baseScale * zoom}px`,
              height: `${sourceImage.naturalHeight * baseScale * zoom}px`,
              transform: `translate(calc(-50% + ${offset.x}px), calc(-50% + ${offset.y}px))`,
          }
        : undefined;

    const clampOffset = (x: number, y: number, zoomValue: number) => {
        if (!sourceImage) return { x: 0, y: 0 };

        const maxX = Math.max(
            0,
            (sourceImage.naturalWidth * baseScale * zoomValue - VIEWPORT_SIZE) /
                2,
        );
        const maxY = Math.max(
            0,
            (sourceImage.naturalHeight * baseScale * zoomValue -
                VIEWPORT_SIZE) /
                2,
        );

        return {
            x: Math.min(maxX, Math.max(-maxX, x)),
            y: Math.min(maxY, Math.max(-maxY, y)),
        };
    };

    const openFilePicker = () => {
        setError(null);
        fileInput.current?.click();
    };

    const handleAvatarClick = () => {
        if (displayedUrl) {
            setPreviewOpen(true);

            return;
        }

        openFilePicker();
    };

    const processFile = (file: File) => {
        setError(null);

        if (!ACCEPTED_TYPES.includes(file.type)) {
            setError(ERRORS.type);
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            setError(ERRORS.size);
            return;
        }

        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            setWorkingUrl(url);
            setSourceImage(image);
            setZoom(1);
            setOffset({ x: 0, y: 0 });
            setDialogOpen(true);
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            setError(ERRORS.read);
        };

        image.src = url;
    };

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        event.target.value = "";

        if (file) processFile(file);
    };

    const handleDrop = (event: DragEvent<HTMLButtonElement>) => {
        event.preventDefault();
        setIsDragging(false);

        const file = event.dataTransfer.files?.[0];

        if (file) processFile(file);
    };

    const closeEditor = () => {
        setWorkingUrl(null);
        setSourceImage(null);
        setDialogOpen(false);
    };

    const startImageDrag = (event: PointerEvent<HTMLDivElement>) => {
        if (!sourceImage) return;

        dragOrigin.current = {
            active: true,
            pointerX: event.clientX,
            pointerY: event.clientY,
            offsetX: offset.x,
            offsetY: offset.y,
        };

        event.currentTarget.setPointerCapture(event.pointerId);
    };

    const onImageDrag = (event: PointerEvent<HTMLDivElement>) => {
        const origin = dragOrigin.current;

        if (!origin.active) return;

        setOffset(
            clampOffset(
                origin.offsetX + (event.clientX - origin.pointerX),
                origin.offsetY + (event.clientY - origin.pointerY),
                zoom,
            ),
        );
    };

    const endImageDrag = () => {
        dragOrigin.current.active = false;
    };

    const handleZoom = (event: ChangeEvent<HTMLInputElement>) => {
        const next = Number(event.target.value);

        setZoom(next);
        setOffset((current) => clampOffset(current.x, current.y, next));
    };

    const confirmCrop = async () => {
        if (!sourceImage) return;

        const canvas = document.createElement("canvas");
        canvas.width = OUTPUT_SIZE;
        canvas.height = OUTPUT_SIZE;

        const context = canvas.getContext("2d");

        if (!context) {
            setError(ERRORS.crop);
            return;
        }

        context.fillStyle = "#ffffff";
        context.fillRect(0, 0, OUTPUT_SIZE, OUTPUT_SIZE);

        const scaleFactor = OUTPUT_SIZE / VIEWPORT_SIZE;
        const drawWidth =
            sourceImage.naturalWidth * baseScale * zoom * scaleFactor;
        const drawHeight =
            sourceImage.naturalHeight * baseScale * zoom * scaleFactor;
        const centerX = OUTPUT_SIZE / 2 + offset.x * scaleFactor;
        const centerY = OUTPUT_SIZE / 2 + offset.y * scaleFactor;

        context.drawImage(
            sourceImage,
            centerX - drawWidth / 2,
            centerY - drawHeight / 2,
            drawWidth,
            drawHeight,
        );

        const blob = await new Promise<Blob | null>((resolve) => {
            canvas.toBlob(resolve, "image/jpeg", 0.9);
        });

        if (!blob) {
            setError(ERRORS.crop);
            return;
        }

        const croppedFile = new File([blob], `avatar-${Date.now()}.jpg`, {
            type: "image/jpeg",
        });

        setPreviewUrl(URL.createObjectURL(croppedFile));
        setRemoved(false);
        onChange(croppedFile);
        closeEditor();
    };

    const removePhoto = () => {
        setError(null);
        setPreviewUrl(null);
        setRemoved(true);
        onChange(null);
        onRemove();
    };

    return (
        <div>
            <div className="flex items-center gap-4">
                <div className="relative">
                    <button
                        type="button"
                        aria-label={
                            displayedUrl
                                ? "View profile photo"
                                : "Choose profile photo"
                        }
                        className={`group relative flex size-25 items-center justify-center overflow-hidden rounded-full border bg-muted transition-colors ${
                            isDragging
                                ? "border-primary ring-2 ring-primary/30"
                                : ""
                        }`}
                        onClick={handleAvatarClick}
                        onDragOver={(event) => {
                            event.preventDefault();
                            setIsDragging(true);
                        }}
                        onDragLeave={() => setIsDragging(false)}
                        onDrop={handleDrop}
                    >
                        {displayedUrl ? (
                            <img
                                src={displayedUrl}
                                alt="Profile photo preview"
                                className="size-full object-cover"
                            />
                        ) : (
                            <span className="text-xl font-medium text-muted-foreground select-none">
                                {getInitials(name)}
                            </span>
                        )}

                        <span className="absolute inset-0 flex items-center justify-center bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100">
                            {displayedUrl ? (
                                <Eye className="size-5" />
                            ) : (
                                <ImagePlus className="size-5" />
                            )}
                        </span>
                    </button>

                    <input
                        ref={fileInput}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="hidden"
                        onChange={handleFileChange}
                    />
                </div>

                <div className="space-y-2">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={openFilePicker}
                        >
                            <RefreshCw />
                            Change photo
                        </Button>

                        {displayedUrl && (
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                onClick={removePhoto}
                            >
                                <Trash2 className="size-4" />
                                Remove
                            </Button>
                        )}
                    </div>

                    <p className="text-xs text-muted-foreground">
                        JPG, PNG, or WebP. Max 2 MB.
                    </p>

                    {error && (
                        <p className="text-xs text-red-600 dark:text-red-400">
                            {error}
                        </p>
                    )}
                </div>
            </div>

            <Dialog
                open={dialogOpen}
                onOpenChange={(open) => {
                    if (!open) closeEditor();
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Adjust photo</DialogTitle>
                        <DialogDescription>
                            Drag to reposition and use the slider to zoom.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex justify-center">
                        <div
                            className="relative size-64 cursor-grab touch-none overflow-hidden rounded-full border bg-muted active:cursor-grabbing"
                            onPointerDown={startImageDrag}
                            onPointerMove={onImageDrag}
                            onPointerUp={endImageDrag}
                            onPointerCancel={endImageDrag}
                        >
                            {workingUrl && (
                                <img
                                    src={workingUrl}
                                    alt="Photo to crop"
                                    className="absolute top-1/2 left-1/2 max-w-none select-none"
                                    style={imageStyle}
                                    draggable={false}
                                />
                            )}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="avatar_zoom">Zoom</Label>
                        <input
                            id="avatar_zoom"
                            type="range"
                            min="1"
                            max="3"
                            step="0.01"
                            value={zoom}
                            onChange={handleZoom}
                            className="w-full accent-primary"
                        />
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={closeEditor}
                        >
                            Cancel
                        </Button>
                        <Button type="button" onClick={confirmCrop}>
                            Use photo
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <AvatarPreview
                url={displayedUrl}
                name={name}
                open={previewOpen}
                onOpenChange={setPreviewOpen}
            />
        </div>
    );
}
